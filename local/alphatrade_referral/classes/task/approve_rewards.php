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

use local_alphatrade_referral\local\engine;

/**
 * Approuve les recompenses dont le delai de validation est ecoule. Personne n'est paye
 * immediatement : le delai couvre la fenetre de remboursement.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve_rewards extends \core\task\scheduled_task {

    /**
     * Nom de la tache.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_approve', 'local_alphatrade_referral');
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
        $count = engine::approve_due_rewards();
        if ($count) {
            mtrace('Parrainage: ' . $count . ' recompense(s) approuvee(s).');
        }
    }
}
