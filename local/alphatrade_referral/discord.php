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
 * Liaison du compte Discord par OAuth2 : départ, retour, et déliaison.
 * Le state est aléatoire et vérifié au retour (ADR-4).
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade_referral\local\discord;
use local_alphatrade_referral\local\engine;

require_login();
require_capability('local/alphatrade_referral:viewown', context_system::instance());

$action = optional_param('action', 'link', PARAM_ALPHA);
$dashboard = new moodle_url('/local/alphatrade_referral/index.php');

if (!engine::enabled() || !discord::configured()) {
    redirect($dashboard, get_string('discord_notconfigured', 'local_alphatrade_referral'), null,
        \core\output\notification::NOTIFY_WARNING);
}

if ($action === 'unlink') {
    require_sesskey();
    discord::unlink($USER->id);
    redirect($dashboard, get_string('discord_unlinked', 'local_alphatrade_referral'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'callback') {
    $code = optional_param('code', '', PARAM_RAW_TRIMMED);
    $state = optional_param('state', '', PARAM_ALPHANUMEXT);
    if ($code === '' || !discord::link($code, $state, (int) $USER->id)) {
        redirect($dashboard, get_string('discord_failed', 'local_alphatrade_referral'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
    redirect($dashboard, get_string('discord_done', 'local_alphatrade_referral'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

redirect(new moodle_url(discord::authorize_url()));
