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

use stdClass;

/**
 * Anti-fraude (§ 10.3). Un signal ne décide jamais seul : il ajoute un score. Au-dessus du seuil,
 * la conversion part en revue manuelle et la récompense est gelée, jamais annulée ni supprimée.
 * Les signaux ne sont pas exposés à l'utilisateur.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fraud {

    /** @var array Poids indicatifs de l'architecture : bloquant, élevé, moyen. */
    const WEIGHTS = [
        'SELF_REFERRAL' => 100,
        'DUPLICATE_ACCOUNT' => 60,
        'SHARED_PAYMENT' => 60,
        'REFUND_ABUSE' => 60,
        'SAME_IP_HASH' => 35,
        'SAME_DEVICE' => 35,
        'RAPID_REPEAT' => 35,
        'CLICK_FLOOD' => 30,
        'CONVERSION_VELOCITY' => 30,
        'CODE_GUESSING' => 30,
    ];

    /**
     * Score une conversion à venir.
     *
     * @param stdClass $relation la relation parrain / filleul
     * @param array $data données de la conversion
     * @return array score, signals, review, blocked
     */
    public static function score(stdClass $relation, array $data = []): array {
        $signals = [];

        if ((int) $relation->referrerid === (int) $relation->referredid) {
            $signals[] = 'SELF_REFERRAL';
        }
        if (self::same_identity($relation)) {
            $signals[] = 'DUPLICATE_ACCOUNT';
        }
        if (self::same_ip($relation)) {
            $signals[] = 'SAME_IP_HASH';
        }
        if (self::rapid_repeat($relation)) {
            $signals[] = 'RAPID_REPEAT';
        }
        if (self::click_flood($relation)) {
            $signals[] = 'CLICK_FLOOD';
        }
        if (self::high_velocity($relation)) {
            $signals[] = 'CONVERSION_VELOCITY';
        }
        if (self::refund_history($relation)) {
            $signals[] = 'REFUND_ABUSE';
        }

        $score = 0;
        foreach ($signals as $signal) {
            $score += self::WEIGHTS[$signal] ?? 0;
        }
        $threshold = (int) engine::config('fraudthreshold', '60');
        return [
            'score' => $score,
            'signals' => $signals,
            'blocked' => in_array('SELF_REFERRAL', $signals, true),
            'review' => $score >= $threshold && !in_array('SELF_REFERRAL', $signals, true),
        ];
    }

    /**
     * Parrain et filleul partagent-ils une identité évidente ? On compare ce que Moodle connaît
     * sans aller chercher de donnée supplémentaire : adresse e-mail et identifiant.
     *
     * @param stdClass $relation
     * @return bool
     */
    protected static function same_identity(stdClass $relation): bool {
        global $DB;

        $fields = 'id, email, username, deleted';
        $referrer = $DB->get_record('user', ['id' => $relation->referrerid], $fields);
        $referred = $DB->get_record('user', ['id' => $relation->referredid], $fields);
        if (!$referrer || !$referred) {
            return false;
        }
        // Même boîte derrière un alias: jean+1@x.com et jean@x.com.
        $normalise = function(string $email): string {
            $parts = explode('@', \core_text::strtolower($email));
            if (count($parts) !== 2) {
                return $email;
            }
            return preg_replace('/\+.*$/', '', $parts[0]) . '@' . $parts[1];
        };
        return $normalise($referrer->email) === $normalise($referred->email);
    }

    /**
     * Le filleul a-t-il cliqué depuis la même empreinte réseau que le parrain ?
     *
     * @param stdClass $relation
     * @return bool
     */
    protected static function same_ip(stdClass $relation): bool {
        global $DB;

        $referrerip = $DB->get_field('user', 'lastip', ['id' => $relation->referrerid]);
        $referredip = $DB->get_field('user', 'lastip', ['id' => $relation->referredid]);
        return $referrerip && $referredip && $referrerip === $referredip;
    }

    /**
     * Plusieurs comptes créés depuis le même code en quelques minutes.
     *
     * @param stdClass $relation
     * @return bool
     */
    protected static function rapid_repeat(stdClass $relation): bool {
        global $DB;

        return $DB->count_records_select('local_atref_relations',
            'codeid = ? AND timecreated > ?', [$relation->codeid, time() - 10 * MINSECS]) > 2;
    }

    /**
     * Clics anormaux sur un code : robot ou rechargement automatique.
     *
     * @param stdClass $relation
     * @return bool
     */
    protected static function click_flood(stdClass $relation): bool {
        global $DB;

        return $DB->count_records_select('local_atref_clicks',
            'codeid = ? AND timecreated > ?', [$relation->codeid, time() - HOURSECS]) > 200;
    }

    /**
     * Taux de conversion statistiquement improbable sur un code (plus de 60 % des clics).
     *
     * @param stdClass $relation
     * @return bool
     */
    protected static function high_velocity(stdClass $relation): bool {
        global $DB;

        $clicks = $DB->count_records('local_atref_clicks', ['codeid' => $relation->codeid]);
        $conversions = $DB->count_records('local_atref_conversions', ['referrerid' => $relation->referrerid]);
        return $clicks >= 10 && $conversions / max(1, $clicks) > 0.6;
    }

    /**
     * Le parrain a déjà des conversions remboursées : le même schéma se répète.
     *
     * @param stdClass $relation
     * @return bool
     */
    protected static function refund_history(stdClass $relation): bool {
        global $DB;

        return $DB->count_records_select('local_atref_conversions',
            'referrerid = ? AND status IN (?, ?)', [$relation->referrerid, 'refunded', 'reversed']) >= 2;
    }

    /**
     * Limite de débit sur le point d'entrée /r/CODE, contre le balayage de codes (§ 10.3.D).
     * Renvoie faux quand la limite est atteinte.
     *
     * @return bool
     */
    public static function allow_click(): bool {
        $limit = (int) engine::config('ratelimit', '30');
        if ($limit <= 0) {
            return true;
        }
        $cache = \cache::make('local_alphatrade_referral', 'ratelimit');
        $key = engine::hash(engine::client_ip()) . '_' . floor(time() / MINSECS);
        $count = (int) $cache->get($key);
        $cache->set($key, $count + 1);
        return $count < $limit;
    }
}
