<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

namespace local_alphatrade_referral\local;

use moodle_url;
use stdClass;

/**
 * Le moteur de parrainage : codes, clics, attribution, conversions, récompenses, journal.
 *
 * Trois règles de l'architecture v2 gouvernent tout ce fichier :
 *   - clic n'est pas conversion, conversion n'est pas récompense (ADR-6) : chaque objet a son état ;
 *   - l'attribution est le premier code valide, et elle est définitive (ADR-5) ;
 *   - un événement externe ne peut produire qu'une conversion (ADR-7), d'où la clé dedupekey.
 *
 * Rien n'est jamais supprimé : un remboursement fait changer d'état, il n'efface pas de ligne.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engine {

    /** @var string Nom du cookie d'attribution. */
    const COOKIE = 'alphatrade_referral';

    /** @var string[] États d'une relation de parrainage. */
    const RELATION_STATES = ['pending', 'active', 'converted', 'rewarded', 'cancelled', 'blocked'];

    /** @var string[] États d'une conversion. */
    const CONVERSION_STATES = ['pending', 'approved', 'under_review', 'refunded', 'reversed', 'blocked'];

    /** @var string[] États d'une récompense. */
    const REWARD_STATES = ['pending', 'approved', 'paid', 'cancelled', 'reversed'];

    /**
     * Réglage du plugin. Les secrets peuvent venir d'une variable d'environnement, qui gagne
     * toujours : aucun secret n'a à être stocké en base ni dans Git (§ 10).
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function config(string $name, string $default = ''): string {
        static $env = [
            'discordclientid' => 'DISCORD_CLIENT_ID',
            'discordclientsecret' => 'DISCORD_CLIENT_SECRET',
            'discordbottoken' => 'DISCORD_BOT_TOKEN',
            'discordguildid' => 'DISCORD_GUILD_ID',
            'apisecret' => 'REFERRAL_API_SECRET',
        ];
        if (isset($env[$name])) {
            $value = getenv($env[$name]);
            if ($value !== false && $value !== '') {
                return (string) $value;
            }
        }
        $value = get_config('local_alphatrade_referral', $name);
        return $value === false || $value === null ? $default : (string) $value;
    }

    /**
     * Le programme est-il actif ?
     *
     * @return bool
     */
    public static function enabled(): bool {
        return self::config('enabled', '1') === '1';
    }

    // ------------------------------------------------------------------ Codes et liens.

    /**
     * Le code du parrain, créé à la première demande.
     *
     * @param int $userid
     * @return stdClass|null
     */
    public static function code_for(int $userid): ?stdClass {
        global $DB;

        $code = $DB->get_record('local_atref_codes', ['userid' => $userid]);
        if ($code) {
            return $code;
        }
        $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname, deleted');
        if (!$user || $user->deleted) {
            return null;
        }
        $record = (object) [
            'userid' => $userid,
            'code' => self::generate_code($user),
            'status' => 'active',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $record->id = $DB->insert_record('local_atref_codes', $record);
        self::log('code_created', ['referrerid' => $userid, 'objecttype' => 'code', 'objectid' => $record->id,
            'payload' => ['code' => $record->code]]);
        return $record;
    }

    /**
     * Un code lisible : le prénom en capitales sans accent, puis trois chiffres, par exemple AMINE742.
     *
     * @param stdClass $user
     * @return string
     */
    protected static function generate_code(stdClass $user): string {
        global $DB;

        $base = \core_text::strtoupper(trim($user->firstname));
        $base = preg_replace('/[^A-Z]/', '', \core_text::specialtoascii($base));
        if (\core_text::strlen($base) < 3) {
            $base = 'ALPHA';
        }
        $base = \core_text::substr($base, 0, 10);
        for ($try = 0; $try < 50; $try++) {
            $code = $base . random_int(100, 999);
            if (!$DB->record_exists('local_atref_codes', ['code' => $code])) {
                return $code;
            }
        }
        // Improbable : on retombe sur un code purement aléatoire plutôt que d'échouer.
        return $base . random_int(100000, 999999);
    }

    /**
     * Le lien de partage du parrain.
     *
     * @param string $code
     * @return string
     */
    public static function link(string $code): string {
        global $CFG;

        if (self::config('shorturl', '1') === '1') {
            return rtrim($CFG->wwwroot, '/') . '/r/' . $code;
        }
        return (new moodle_url('/local/alphatrade_referral/r.php', ['c' => $code]))->out(false);
    }

    // ------------------------------------------------------------------ Clics et attribution.

    /**
     * Enregistre un clic et pose le cookie d'attribution. Le cookie n'est jamais écrasé :
     * c'est le premier code valide qui compte (ADR-5).
     *
     * @param stdClass $code
     * @param string $landingpage
     * @return void
     */
    public static function record_click(stdClass $code, string $landingpage): void {
        global $DB, $CFG;

        $DB->insert_record('local_atref_clicks', (object) [
            'codeid' => $code->id,
            'sessionid' => substr(sha1(session_id() . $CFG->wwwroot), 0, 64),
            'visitorid' => substr(sha1(self::client_ip() . self::user_agent() . $CFG->wwwroot), 0, 64),
            'campaignid' => $code->campaignid,
            'landingpage' => \core_text::substr($landingpage, 0, 255),
            'iphash' => self::hash(self::client_ip()),
            'uahash' => self::hash(self::user_agent()),
            'timecreated' => time(),
        ]);

        if (empty($_COOKIE[self::COOKIE])) {
            $days = max(1, (int) self::config('cookiedays', '30'));
            setcookie(self::COOKIE, $code->code, [
                'expires' => time() + $days * DAYSECS,
                'path' => '/',
                'secure' => strpos($CFG->wwwroot, 'https://') === 0,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    /**
     * Attribue un parrain au compte qui vient d'être créé, à partir du cookie. L'auto-parrainage
     * est refusé dès ici et la tentative est journalisée (§ 10.3.B).
     *
     * @param int $referredid
     * @return stdClass|null la relation créée
     */
    public static function attribute(int $referredid): ?stdClass {
        global $DB;

        if (!self::enabled() || empty($_COOKIE[self::COOKIE])) {
            return null;
        }
        $code = $DB->get_record('local_atref_codes',
            ['code' => clean_param($_COOKIE[self::COOKIE], PARAM_ALPHANUM), 'status' => 'active']);
        if (!$code) {
            return null;
        }
        if ($DB->record_exists('local_atref_relations', ['referredid' => $referredid])) {
            // Un filleul n'a qu'un seul parrain, et il est définitif.
            return null;
        }
        if ((int) $code->userid === $referredid) {
            self::log('self_referral_blocked', ['referrerid' => $code->userid, 'referredid' => $referredid,
                'objecttype' => 'code', 'objectid' => $code->id]);
            return null;
        }
        $relation = (object) [
            'referrerid' => $code->userid,
            'referredid' => $referredid,
            'codeid' => $code->id,
            'status' => 'pending',
            'timecreated' => time(),
        ];
        $relation->id = $DB->insert_record('local_atref_relations', $relation);
        self::log('relation_created', ['referrerid' => $relation->referrerid, 'referredid' => $referredid,
            'objecttype' => 'relation', 'objectid' => $relation->id, 'payload' => ['code' => $code->code]]);
        return $relation;
    }

    // ------------------------------------------------------------------ Conversions et récompenses.

    /**
     * Enregistre une conversion pour un filleul. Idempotent : la même clé ne produit qu'une ligne,
     * quel que soit le nombre d'appels (ADR-7).
     *
     * @param int $referredid le filleul
     * @param array $data dedupekey, conversiontype, amount, currency, orderid, enrolmentid, courseid
     * @return stdClass|null la conversion, ou null si pas de parrain ou si déjà enregistrée
     */
    public static function record_conversion(int $referredid, array $data): ?stdClass {
        global $DB;

        if (!self::enabled() || empty($data['dedupekey'])) {
            return null;
        }
        $relation = $DB->get_record('local_atref_relations', ['referredid' => $referredid]);
        if (!$relation || in_array($relation->status, ['cancelled', 'blocked'], true)) {
            return null;
        }
        if ($DB->record_exists('local_atref_conversions', ['dedupekey' => $data['dedupekey']])) {
            return null;
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : null;
        $currency = $data['currency'] ?? self::config('currency', 'EUR');
        $scoring = fraud::score($relation, $data);

        $conversion = (object) [
            'relationid' => $relation->id,
            'referrerid' => $relation->referrerid,
            'referredid' => $referredid,
            'dedupekey' => \core_text::substr($data['dedupekey'], 0, 160),
            'orderid' => $data['orderid'] ?? null,
            'enrolmentid' => $data['enrolmentid'] ?? null,
            'courseid' => $data['courseid'] ?? null,
            'amount' => $amount,
            'commission' => self::commission($amount),
            'currency' => $currency,
            'conversiontype' => $data['conversiontype'] ?? 'payment',
            'status' => $scoring['blocked'] ? 'blocked' : ($scoring['review'] ? 'under_review' : 'pending'),
            'score' => $scoring['score'],
            'signals' => json_encode($scoring['signals'], JSON_UNESCAPED_UNICODE),
            'timecreated' => time(),
        ];
        $conversion->id = $DB->insert_record('local_atref_conversions', $conversion);
        self::log('conversion_created', ['referrerid' => $conversion->referrerid, 'referredid' => $referredid,
            'objecttype' => 'conversion', 'objectid' => $conversion->id,
            'payload' => ['status' => $conversion->status, 'score' => $scoring['score'], 'signals' => $scoring['signals']]]);

        if ($relation->status === 'pending' || $relation->status === 'active') {
            $DB->set_field('local_atref_relations', 'status', 'converted', ['id' => $relation->id]);
            $DB->set_field('local_atref_relations', 'timevalidated', time(), ['id' => $relation->id]);
        }

        // La récompense existe dès la conversion, gelée tant que la conversion n'est pas approuvée :
        // elle change d'état, elle n'est jamais créée après coup ni supprimée.
        $reward = (object) [
            'conversionid' => $conversion->id,
            'referrerid' => $conversion->referrerid,
            'rewardtype' => self::config('rewardtype', 'fixed'),
            'amount' => self::commission($amount) ?? 0,
            'currency' => $currency,
            'status' => 'pending',
            'timeavailable' => time() + max(0, (int) self::config('validationdays', '14')) * DAYSECS,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $reward->id = $DB->insert_record('local_atref_rewards', $reward);
        self::log('reward_created', ['referrerid' => $reward->referrerid, 'referredid' => $referredid,
            'objecttype' => 'reward', 'objectid' => $reward->id,
            'payload' => ['amount' => $reward->amount, 'available' => $reward->timeavailable]]);

        discord::queue('conversion', $conversion->referrerid, ['conversionid' => $conversion->id]);
        return $conversion;
    }

    /**
     * Le montant de la récompense pour une conversion.
     *
     * @param float|null $amount montant payé
     * @return float|null
     */
    protected static function commission(?float $amount): ?float {
        $type = self::config('rewardtype', 'fixed');
        if ($type === 'percent') {
            return $amount === null ? null : round($amount * (float) self::config('rewardpercent', '10') / 100, 2);
        }
        return round((float) self::config('rewardamount', '10'), 2);
    }

    /**
     * Cron : approuve les récompenses dont le délai de validation est écoulé et dont la conversion
     * est saine. Personne n'est payé immédiatement (§ 10.3.D).
     *
     * @return int nombre de récompenses approuvées
     */
    public static function approve_due_rewards(): int {
        global $DB;

        $sql = "SELECT r.*, c.status AS conversionstatus
                  FROM {local_atref_rewards} r
                  JOIN {local_atref_conversions} c ON c.id = r.conversionid
                 WHERE r.status = :pending AND r.timeavailable <= :now";
        $rewards = $DB->get_records_sql($sql, ['pending' => 'pending', 'now' => time()]);
        $count = 0;
        foreach ($rewards as $reward) {
            if ($reward->conversionstatus === 'pending') {
                // La conversion passe approuvée en même temps que sa récompense.
                $DB->update_record('local_atref_conversions', (object) [
                    'id' => $reward->conversionid, 'status' => 'approved', 'timevalidated' => time()]);
            } else if ($reward->conversionstatus !== 'approved') {
                // Sous revue, remboursée ou bloquée : la récompense reste gelée, elle n'est pas annulée.
                continue;
            }
            $DB->update_record('local_atref_rewards', (object) [
                'id' => $reward->id, 'status' => 'approved', 'timeapproved' => time(), 'timemodified' => time()]);
            $referredid = (int) $DB->get_field('local_atref_conversions', 'referredid', ['id' => $reward->conversionid]);
            if ($referredid) {
                $DB->set_field('local_atref_relations', 'status', 'rewarded', ['referredid' => $referredid]);
            }
            self::log('reward_approved', ['referrerid' => $reward->referrerid, 'objecttype' => 'reward',
                'objectid' => $reward->id, 'payload' => ['amount' => $reward->amount]]);
            discord::queue('reward', $reward->referrerid, ['rewardid' => $reward->id]);
            $count++;
        }
        return $count;
    }

    /**
     * Remboursement : la conversion passe remboursée, la récompense est contre-passée.
     * Les deux lignes restent en base (§ 6.2).
     *
     * @param int $conversionid
     * @param string $reason
     * @param int|null $adminid
     * @return void
     */
    public static function reverse(int $conversionid, string $reason, ?int $adminid = null): void {
        global $DB;

        $conversion = $DB->get_record('local_atref_conversions', ['id' => $conversionid], '*', MUST_EXIST);
        $DB->update_record('local_atref_conversions', (object) [
            'id' => $conversion->id, 'status' => 'refunded', 'reason' => \core_text::substr($reason, 0, 255),
            'timereversed' => time()]);
        foreach ($DB->get_records('local_atref_rewards', ['conversionid' => $conversion->id]) as $reward) {
            $DB->update_record('local_atref_rewards', (object) [
                'id' => $reward->id, 'status' => 'reversed', 'timereversed' => time(), 'timemodified' => time()]);
        }
        self::log('conversion_reversed', ['userid' => $adminid, 'referrerid' => $conversion->referrerid,
            'referredid' => $conversion->referredid, 'objecttype' => 'conversion', 'objectid' => $conversion->id,
            'payload' => ['reason' => $reason]]);
    }

    /**
     * Décision d'un administrateur sur une conversion en revue (§ 10.3.C). Le motif est obligatoire,
     * l'auteur et la date sont journalisés.
     *
     * @param int $conversionid
     * @param string $action approve|block|hold
     * @param string $reason
     * @param int $adminid
     * @return void
     */
    public static function decide(int $conversionid, string $action, string $reason, int $adminid): void {
        global $DB;

        $conversion = $DB->get_record('local_atref_conversions', ['id' => $conversionid], '*', MUST_EXIST);
        $states = ['approve' => 'approved', 'block' => 'blocked', 'hold' => 'under_review'];
        if (!isset($states[$action])) {
            throw new \coding_exception('Action de revue inconnue: ' . $action);
        }
        $update = (object) ['id' => $conversion->id, 'status' => $states[$action],
            'reason' => \core_text::substr($reason, 0, 255)];
        if ($action === 'approve') {
            $update->timevalidated = time();
        }
        $DB->update_record('local_atref_conversions', $update);

        foreach ($DB->get_records('local_atref_rewards', ['conversionid' => $conversion->id]) as $reward) {
            if ($action === 'block') {
                $DB->update_record('local_atref_rewards', (object) [
                    'id' => $reward->id, 'status' => 'cancelled', 'timemodified' => time()]);
            } else if ($action === 'approve' && $reward->status === 'pending' && $reward->timeavailable <= time()) {
                $DB->update_record('local_atref_rewards', (object) [
                    'id' => $reward->id, 'status' => 'approved', 'timeapproved' => time(), 'timemodified' => time()]);
                discord::queue('reward', $reward->referrerid, ['rewardid' => $reward->id]);
            }
        }
        if ($action === 'block') {
            $DB->set_field('local_atref_relations', 'status', 'blocked', ['id' => $conversion->relationid]);
        }
        self::log('review_decision', ['userid' => $adminid, 'referrerid' => $conversion->referrerid,
            'referredid' => $conversion->referredid, 'objecttype' => 'conversion', 'objectid' => $conversion->id,
            'payload' => ['action' => $action, 'reason' => $reason]]);
    }

    // ------------------------------------------------------------------ Lectures.

    /**
     * Les chiffres d'un parrain, pour son tableau de bord et pour le bot.
     *
     * @param int $userid
     * @return array
     */
    public static function stats(int $userid): array {
        global $DB;

        $code = self::code_for($userid);
        $clicks = $code ? $DB->count_records('local_atref_clicks', ['codeid' => $code->id]) : 0;
        $referrals = $DB->count_records('local_atref_relations', ['referrerid' => $userid]);
        $converted = $DB->count_records_select('local_atref_relations',
            'referrerid = ? AND status IN (?, ?)', [$userid, 'converted', 'rewarded']);
        $pending = (float) $DB->get_field_sql(
            'SELECT COALESCE(SUM(amount), 0) FROM {local_atref_rewards} WHERE referrerid = ? AND status = ?',
            [$userid, 'pending']);
        $earned = (float) $DB->get_field_sql(
            'SELECT COALESCE(SUM(amount), 0) FROM {local_atref_rewards} WHERE referrerid = ? AND status IN (?, ?)',
            [$userid, 'approved', 'paid']);
        return [
            'code' => $code ? $code->code : '',
            'link' => $code ? self::link($code->code) : '',
            'clicks' => (int) $clicks,
            'referrals' => (int) $referrals,
            'conversions' => (int) $converted,
            'pending' => $pending,
            'earned' => $earned,
            'currency' => self::config('currency', 'EUR'),
        ];
    }

    /**
     * Les filleuls d'un parrain, données minimisées (§ 9.5) : prénom et première lettre du nom.
     *
     * @param int $userid
     * @param int $limit
     * @return array
     */
    public static function referrals(int $userid, int $limit = 100): array {
        global $DB;

        $sql = "SELECT r.id, r.status, r.timecreated, u.firstname, u.lastname
                  FROM {local_atref_relations} r
                  JOIN {user} u ON u.id = r.referredid
                 WHERE r.referrerid = :userid AND u.deleted = 0
              ORDER BY r.timecreated DESC";
        $rows = [];
        foreach ($DB->get_records_sql($sql, ['userid' => $userid], 0, $limit) as $row) {
            $rows[] = [
                'name' => $row->firstname . ' ' . \core_text::substr($row->lastname, 0, 1) . '.',
                'status' => $row->status,
                'statuslabel' => get_string('relation_' . $row->status, 'local_alphatrade_referral'),
                'date' => userdate($row->timecreated, get_string('strftimedatefullshort', 'core_langconfig')),
            ];
        }
        return $rows;
    }

    /**
     * Les récompenses d'un parrain.
     *
     * @param int $userid
     * @param int $limit
     * @return array
     */
    public static function rewards(int $userid, int $limit = 100): array {
        global $DB;

        $rows = [];
        $records = $DB->get_records('local_atref_rewards', ['referrerid' => $userid], 'timecreated DESC', '*', 0, $limit);
        foreach ($records as $reward) {
            $rows[] = [
                'amount' => format_float($reward->amount, 2),
                'currency' => $reward->currency,
                'status' => $reward->status,
                'statuslabel' => get_string('reward_' . $reward->status, 'local_alphatrade_referral'),
                'available' => userdate($reward->timeavailable, get_string('strftimedatefullshort', 'core_langconfig')),
                'ispending' => $reward->status === 'pending',
            ];
        }
        return $rows;
    }

    // ------------------------------------------------------------------ Journal et utilitaires.

    /**
     * Écrit une ligne dans le journal d'audit. Ce journal n'est jamais purgé : il permet de
     * reconstruire tout l'historique (§ 5.8).
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public static function log(string $type, array $data = []): void {
        global $DB;

        $DB->insert_record('local_atref_events', (object) [
            'eventtype' => \core_text::substr($type, 0, 64),
            'userid' => $data['userid'] ?? null,
            'referrerid' => $data['referrerid'] ?? null,
            'referredid' => $data['referredid'] ?? null,
            'objecttype' => $data['objecttype'] ?? null,
            'objectid' => $data['objectid'] ?? null,
            'payload' => isset($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE) : null,
            'timecreated' => time(),
        ]);
    }

    /**
     * Hachage d'une donnée technique, salé par la clé du site : jamais d'IP en clair (§ 10 RGPD).
     *
     * @param string $value
     * @return string
     */
    public static function hash(string $value): string {
        global $CFG;

        return hash('sha256', $value . ($CFG->siteidentifier ?? $CFG->wwwroot));
    }

    /**
     * IP du visiteur.
     *
     * @return string
     */
    public static function client_ip(): string {
        return (string) getremoteaddr('0.0.0.0');
    }

    /**
     * Agent utilisateur du visiteur.
     *
     * @return string
     */
    public static function user_agent(): string {
        return (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    }
}
