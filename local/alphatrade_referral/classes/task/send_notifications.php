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

namespace local_alphatrade_referral\task;

use local_alphatrade_referral\local\discord;
use local_alphatrade_referral\local\engine;

/**
 * Vide la file des notifications Discord. Un echec ne bloque rien : le message repart au
 * passage suivant, jusqu'a la limite d'essais.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_notifications extends \core\task\scheduled_task {

    /**
     * Nom de la tache.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_notify', 'local_alphatrade_referral');
    }

    /**
     * Execution.
     *
     * @return void
     */
    public function execute(): void {
        if (!engine::enabled()) {
            return;
        }
        list($sent, $failed) = discord::flush();
        if ($sent || $failed) {
            mtrace('Parrainage Discord: ' . $sent . ' envoyee(s), ' . $failed . ' en attente.');
        }
    }
}
