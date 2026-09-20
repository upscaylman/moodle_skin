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
 * Point d'entrée public du lien de parrainage : /r/CODE (réécrit par Apache) ou r.php?c=CODE.
 * Vérifie le code, enregistre le clic, pose le cookie d'attribution et redirige vers le site.
 * Aucune authentification : c'est un lien de partage public.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade_referral\local\engine;
use local_alphatrade_referral\local\fraud;

$code = optional_param('c', '', PARAM_ALPHANUM);
$landing = new moodle_url(engine::config('landingpath', '/'));

if (!engine::enabled() || $code === '') {
    redirect($landing);
}

// Limite de débit : contre le balayage de codes, on redirige sans rien enregistrer.
if (!fraud::allow_click()) {
    engine::log('rate_limited', ['payload' => ['code' => $code]]);
    redirect($landing);
}

$record = $DB->get_record('local_atref_codes', ['code' => $code, 'status' => 'active']);
if (!$record) {
    engine::log('code_unknown', ['payload' => ['code' => $code]]);
    redirect($landing);
}

engine::record_click($record, $landing->out(false));
redirect($landing);
