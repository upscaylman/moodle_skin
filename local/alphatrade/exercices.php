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
 * Pilier Exercices : à faire, en attente de correction, terminés - devoirs et quiz du
 * programme (architecture Alpha Trade § 9).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\pillars;

page::setup('/local/alphatrade/exercices.php', 'exercices', get_string('exercices', 'local_alphatrade'), [],
    get_string('sub_exercices', 'local_alphatrade'));

$data = pillars::exercises($USER->id);
$data['parcoursurl'] = (new moodle_url('/local/alphatrade/parcours.php'))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/exercices', $data);
echo $OUTPUT->footer();
