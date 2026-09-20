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

namespace local_alphatrade_referral;

use local_alphatrade_referral\local\engine;

/**
 * Les points d'accroche Moodle du moteur : création de compte (attribution) et inscription
 * (conversion, quand le programme est réglé sur l'inscription plutôt que sur le paiement).
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Un compte vient d'être créé : on lit le cookie d'attribution et on fige le parrain.
     *
     * @param \core\event\user_created $event
     * @return void
     */
    public static function user_created(\core\event\user_created $event): void {
        engine::attribute((int) $event->objectid);
    }

    /**
     * Un compte est supprimé : le code est désactivé, la liaison Discord retirée. Les conversions
     * restent, obligations comptables obligent (§ 10.2).
     *
     * @param \core\event\user_deleted $event
     * @return void
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;

        $userid = (int) $event->objectid;
        $DB->set_field('local_atref_codes', 'status', 'disabled', ['userid' => $userid]);
        $DB->delete_records('local_atref_discord', ['userid' => $userid]);
        engine::log('user_deleted', ['referrerid' => $userid]);
    }

    /**
     * Une inscription vient d'être créée. Elle ne vaut conversion que si le programme est réglé
     * sur l'inscription : par défaut, la conversion attend le paiement confirmé (§ 11).
     *
     * @param \core\event\user_enrolment_created $event
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): void {
        if (!in_array(engine::config('conversiontrigger', 'payment'), ['enrolment', 'both'], true)) {
            return;
        }
        engine::record_conversion((int) $event->relateduserid, [
            'dedupekey' => 'enrolment:' . $event->objectid,
            'conversiontype' => 'enrolment',
            'enrolmentid' => (int) $event->objectid,
            'courseid' => (int) $event->courseid,
        ]);
    }
}
