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

use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_alphatrade_referral\local\engine;

/**
 * Filleuls d'un membre, pour la commande /filleuls du bot. Donnees minimisees : prenom et
 * initiale du nom, jamais d'e-mail ni de donnees pedagogiques (§ 9.5).
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_referrals extends external_api {

    use resolver;

    /**
     * Execution.
     *
     * @param string $discorduserid
     * @return array
     */
    public static function execute(string $discorduserid): array {
        $rows = [];
        foreach (engine::referrals(self::resolve($discorduserid), 25) as $row) {
            $rows[] = ['name' => $row['name'], 'status' => $row['status'], 'date' => $row['date']];
        }
        return $rows;
    }

    /**
     * Structure de retour.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'name' => new external_value(PARAM_TEXT, 'Prenom et initiale'),
            'status' => new external_value(PARAM_ALPHA, 'Etat de la relation'),
            'date' => new external_value(PARAM_TEXT, 'Date d attribution'),
        ]));
    }
}
