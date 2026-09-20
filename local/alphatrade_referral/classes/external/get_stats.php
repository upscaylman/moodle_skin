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
use core_external\external_single_structure;
use core_external\external_value;
use local_alphatrade_referral\local\engine;

/**
 * Statistiques de parrainage d'un membre, pour la commande /statistiques du bot.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_stats extends external_api {

    use resolver;

    /**
     * Execution.
     *
     * @param string $discorduserid
     * @return array
     */
    public static function execute(string $discorduserid): array {
        $stats = engine::stats(self::resolve($discorduserid));
        return [
            'clicks' => $stats['clicks'],
            'referrals' => $stats['referrals'],
            'conversions' => $stats['conversions'],
            'pending' => (float) $stats['pending'],
            'earned' => (float) $stats['earned'],
            'currency' => $stats['currency'],
        ];
    }

    /**
     * Structure de retour.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'clicks' => new external_value(PARAM_INT, 'Clics sur le lien'),
            'referrals' => new external_value(PARAM_INT, 'Filleuls attribues'),
            'conversions' => new external_value(PARAM_INT, 'Filleuls convertis'),
            'pending' => new external_value(PARAM_FLOAT, 'Recompenses en attente'),
            'earned' => new external_value(PARAM_FLOAT, 'Recompenses approuvees ou versees'),
            'currency' => new external_value(PARAM_ALPHA, 'Devise'),
        ]);
    }
}
