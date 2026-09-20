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
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Services web consommes par le bot Discord. Chaque appel est authentifie par un jeton Moodle
 * et ne renvoie jamais les donnees d'un autre membre.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_alphatrade_referral_get_stats' => [
        'classname' => 'local_alphatrade_referral\external\get_stats',
        'description' => 'Statistiques de parrainage du membre lie au compte Discord.',
        'type' => 'read',
        'capabilities' => 'local/alphatrade_referral:viewown',
        'services' => ['local_alphatrade_referral_bot'],
    ],
    'local_alphatrade_referral_get_link' => [
        'classname' => 'local_alphatrade_referral\external\get_link',
        'description' => 'Code et lien de parrainage du membre.',
        'type' => 'read',
        'capabilities' => 'local/alphatrade_referral:viewown',
        'services' => ['local_alphatrade_referral_bot'],
    ],
    'local_alphatrade_referral_get_referrals' => [
        'classname' => 'local_alphatrade_referral\external\get_referrals',
        'description' => 'Filleuls du membre, donnees minimisees.',
        'type' => 'read',
        'capabilities' => 'local/alphatrade_referral:viewown',
        'services' => ['local_alphatrade_referral_bot'],
    ],
    'local_alphatrade_referral_get_rewards' => [
        'classname' => 'local_alphatrade_referral\external\get_rewards',
        'description' => 'Recompenses du membre.',
        'type' => 'read',
        'capabilities' => 'local/alphatrade_referral:viewown',
        'services' => ['local_alphatrade_referral_bot'],
    ],
];

$services = [
    'Alpha Trade referral bot' => [
        'functions' => [
            'local_alphatrade_referral_get_stats',
            'local_alphatrade_referral_get_link',
            'local_alphatrade_referral_get_referrals',
            'local_alphatrade_referral_get_rewards',
        ],
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'local_alphatrade_referral_bot',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
