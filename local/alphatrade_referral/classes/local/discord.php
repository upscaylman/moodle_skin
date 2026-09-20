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

use curl;
use moodle_url;

/**
 * Discord : liaison de compte par OAuth2 et file de notifications.
 *
 * Discord est une interface, jamais une source de données (ADR-2), et il n'est jamais bloquant
 * (ADR-8) : tout ce qui part vers Discord passe par une file rejouée par le cron. Une panne
 * Discord n'empêche donc aucun traitement Moodle.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discord {

    /** @var string Point d'autorisation OAuth2. */
    const AUTHORIZE = 'https://discord.com/api/oauth2/authorize';

    /** @var string Échange du code contre un jeton. */
    const TOKEN = 'https://discord.com/api/oauth2/token';

    /** @var string Identité du porteur du jeton. */
    const ME = 'https://discord.com/api/users/@me';

    /** @var int Nombre d'essais avant d'abandonner une notification. */
    const MAX_ATTEMPTS = 8;

    /**
     * La liaison Discord est-elle configurée ?
     *
     * @return bool
     */
    public static function configured(): bool {
        return engine::config('discordclientid') !== '' && engine::config('discordclientsecret') !== '';
    }

    /**
     * URL d'autorisation, avec un state aléatoire stocké en session et vérifié au retour (ADR-4).
     *
     * @return string
     */
    public static function authorize_url(): string {
        global $SESSION;

        $state = bin2hex(random_bytes(16));
        $SESSION->local_atref_state = $state;
        return self::AUTHORIZE . '?' . http_build_query([
            'client_id' => engine::config('discordclientid'),
            'redirect_uri' => self::redirect_uri(),
            'response_type' => 'code',
            'scope' => 'identify',
            'state' => $state,
            'prompt' => 'consent',
        ], '', '&');
    }

    /**
     * URL de retour déclarée dans l'application Discord.
     *
     * @return string
     */
    public static function redirect_uri(): string {
        return (new moodle_url('/local/alphatrade_referral/discord.php', ['action' => 'callback']))->out(false);
    }

    /**
     * Termine la liaison : vérifie le state, échange le code, enregistre le discord_user_id.
     *
     * @param string $code
     * @param string $state
     * @param int $userid
     * @return bool
     */
    public static function link(string $code, string $state, int $userid): bool {
        global $DB, $SESSION;

        $expected = $SESSION->local_atref_state ?? '';
        unset($SESSION->local_atref_state);
        if ($expected === '' || !hash_equals($expected, $state)) {
            engine::log('discord_state_mismatch', ['userid' => $userid]);
            return false;
        }

        $curl = new curl();
        $response = $curl->post(self::TOKEN, [
            'client_id' => engine::config('discordclientid'),
            'client_secret' => engine::config('discordclientsecret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::redirect_uri(),
        ], ['CURLOPT_TIMEOUT' => 15]);
        $token = json_decode((string) $response, true);
        if (empty($token['access_token'])) {
            engine::log('discord_token_failed', ['userid' => $userid]);
            return false;
        }

        $curl = new curl();
        $curl->setHeader('Authorization: Bearer ' . $token['access_token']);
        $me = json_decode((string) $curl->get(self::ME, [], ['CURLOPT_TIMEOUT' => 15]), true);
        if (empty($me['id'])) {
            engine::log('discord_identity_failed', ['userid' => $userid]);
            return false;
        }

        // Un compte Discord ne peut être lié qu'à un seul compte Moodle : l'ancienne liaison saute.
        $DB->delete_records('local_atref_discord', ['discorduserid' => $me['id']]);
        $DB->delete_records('local_atref_discord', ['userid' => $userid]);
        $DB->insert_record('local_atref_discord', (object) [
            'userid' => $userid,
            'discorduserid' => $me['id'],
            'discordname' => \core_text::substr($me['username'] ?? '', 0, 64),
            'guildid' => engine::config('discordguildid') ?: null,
            'status' => 'linked',
            'timelinked' => time(),
        ]);
        engine::log('discord_linked', ['userid' => $userid, 'payload' => ['discorduserid' => $me['id']]]);
        return true;
    }

    /**
     * Délie le compte. L'historique de parrainage reste intact (§ 10.2).
     *
     * @param int $userid
     * @return void
     */
    public static function unlink(int $userid): void {
        global $DB;

        $DB->delete_records('local_atref_discord', ['userid' => $userid]);
        engine::log('discord_unlinked', ['userid' => $userid]);
    }

    /**
     * La liaison d'un utilisateur.
     *
     * @param int $userid
     * @return \stdClass|null
     */
    public static function account(int $userid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_atref_discord', ['userid' => $userid]) ?: null;
    }

    /**
     * Met un message en file. On n'appelle jamais Discord pendant le traitement Moodle.
     *
     * @param string $kind conversion|reward|role
     * @param int $userid destinataire
     * @param array $payload
     * @return void
     */
    public static function queue(string $kind, int $userid, array $payload = []): void {
        global $DB;

        if (engine::config('discordnotify', '1') !== '1') {
            return;
        }
        $DB->insert_record('local_atref_queue', (object) [
            'kind' => $kind,
            'userid' => $userid,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'attempts' => 0,
            'timecreated' => time(),
        ]);
    }

    /**
     * Cron : vide la file. Un échec laisse le message en attente pour le prochain passage.
     *
     * @param int $limit
     * @return array envoyés, échoués
     */
    public static function flush(int $limit = 50): array {
        global $DB;

        $sent = 0;
        $failed = 0;
        $messages = $DB->get_records_select('local_atref_queue', 'status = ? AND attempts < ?',
            ['pending', self::MAX_ATTEMPTS], 'timecreated ASC', '*', 0, $limit);
        foreach ($messages as $message) {
            $result = self::send($message);
            $update = (object) ['id' => $message->id, 'attempts' => $message->attempts + 1];
            if ($result === true) {
                $update->status = 'sent';
                $update->timesent = time();
                $sent++;
            } else {
                $update->lasterror = \core_text::substr((string) $result, 0, 255);
                if ($update->attempts >= self::MAX_ATTEMPTS) {
                    $update->status = 'failed';
                }
                $failed++;
            }
            $DB->update_record('local_atref_queue', $update);
        }
        return [$sent, $failed];
    }

    /**
     * Envoie un message au membre par message privé Discord. Le contenu reste neutre : pseudonyme,
     * statut, montant. Jamais d'e-mail, de données de paiement ni de signal de fraude (§ 9.5, § 10.3.E).
     *
     * @param \stdClass $message
     * @return bool|string vrai, ou le message d'erreur
     */
    protected static function send(\stdClass $message) {
        global $DB;

        $token = engine::config('discordbottoken');
        if ($token === '') {
            return 'bot token absent';
        }
        $account = self::account((int) $message->userid);
        if (!$account) {
            // Le membre n'a pas lié Discord : rien à envoyer, ce n'est pas une erreur.
            return true;
        }
        $text = self::text($message);
        if ($text === '') {
            return true;
        }

        $curl = new curl();
        $curl->setHeader(['Authorization: Bot ' . $token, 'Content-Type: application/json']);
        $channel = json_decode((string) $curl->post('https://discord.com/api/v10/users/@me/channels',
            json_encode(['recipient_id' => $account->discorduserid]), ['CURLOPT_TIMEOUT' => 15]), true);
        if (empty($channel['id'])) {
            return 'ouverture du salon prive impossible';
        }
        $curl = new curl();
        $curl->setHeader(['Authorization: Bot ' . $token, 'Content-Type: application/json']);
        $curl->post('https://discord.com/api/v10/channels/' . $channel['id'] . '/messages',
            json_encode(['content' => $text]), ['CURLOPT_TIMEOUT' => 15]);
        $info = $curl->get_info();
        if (($info['http_code'] ?? 0) >= 300) {
            return 'HTTP ' . $info['http_code'];
        }
        return true;
    }

    /**
     * Le texte d'une notification.
     *
     * @param \stdClass $message
     * @return string
     */
    protected static function text(\stdClass $message): string {
        global $DB;

        $payload = json_decode((string) $message->payload, true) ?: [];
        if ($message->kind === 'conversion') {
            return get_string('notify_conversion', 'local_alphatrade_referral');
        }
        if ($message->kind === 'reward') {
            $reward = $DB->get_record('local_atref_rewards', ['id' => $payload['rewardid'] ?? 0]);
            if (!$reward) {
                return '';
            }
            return get_string('notify_reward', 'local_alphatrade_referral',
                format_float($reward->amount, 2) . ' ' . $reward->currency);
        }
        return '';
    }

    /**
     * Le rôle communautaire mérité par un parrain, d'après ses conversions validées (§ 9.3).
     *
     * @param int $userid
     * @return string clé du rôle, ou chaîne vide
     */
    public static function role_for(int $userid): string {
        global $DB;

        $count = $DB->count_records_select('local_atref_conversions',
            'referrerid = ? AND status = ?', [$userid, 'approved']);
        $levels = [
            'super' => (int) engine::config('rolesuper', '20'),
            'ambassador' => (int) engine::config('roleambassador', '5'),
            'sponsor' => (int) engine::config('rolesponsor', '1'),
        ];
        foreach ($levels as $key => $threshold) {
            if ($threshold > 0 && $count >= $threshold) {
                return $key;
            }
        }
        return '';
    }
}
