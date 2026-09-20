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

namespace local_alphatrade_referral\external;

use core_external\external_value;

/**
 * Point commun des services du bot : un identifiant Discord entre, un utilisateur Moodle sort.
 * Discord n'est qu'une liaison secondaire, la cle principale reste l'identifiant Moodle.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait resolver {

    /**
     * Parametres communs.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'discorduserid' => new external_value(PARAM_ALPHANUMEXT, 'Identifiant Discord du membre'),
        ]);
    }

    /**
     * L'utilisateur Moodle lie a ce compte Discord.
     *
     * @param string $discorduserid
     * @return int
     */
    protected static function resolve(string $discorduserid): int {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['discorduserid' => $discorduserid]);
        self::validate_context(\context_system::instance());
        require_capability('local/alphatrade_referral:viewown', \context_system::instance());

        $userid = (int) $DB->get_field('local_atref_discord', 'userid',
            ['discorduserid' => $params['discorduserid']]);
        if (!$userid) {
            throw new \moodle_exception('notlinked', 'local_alphatrade_referral');
        }
        return $userid;
    }
}
