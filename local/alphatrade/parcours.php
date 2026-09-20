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
 * "Mon parcours" (maquette Portail Etudiant, screen "Parcours").
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/parcours.php', 'parcours', get_string('parcours', 'local_alphatrade'), [],
    get_string('sub_parcours', 'local_alphatrade'));

$data = ['hasprogramme' => false];
$programme = programme::for_user($USER->id);
if ($programme) {
    $summary = $programme->get_summary();
    $months = [];
    foreach ($programme->get_months() as $month) {
        $modules = [];
        foreach ($month['modules'] as $module) {
            $modules[] = [
                'number' => $module['number'],
                'label' => $module['label'],
                'name' => $module['name'],
                'url' => $module['url'],
                'meta' => get_string('modulemeta', 'local_alphatrade',
                    ['lessons' => $module['lessoncount'], 'percent' => $module['percent']]),
                'islocked' => $module['islocked'],
                'iscurrent' => $module['iscurrent'],
                'isdone' => $module['isdone'],
                'iconclass' => programme::maquette_icon($module['status']),
                'statusclass' => programme::maquette_status_class($module['status']),
            ];
        }
        $months[] = [
            'number' => $month['number'],
            'label' => core_text::strtoupper(get_string('monthlabel', 'local_alphatrade', $month['number'])),
            'theme' => core_text::strtoupper($month['theme']),
            'modules' => $modules,
        ];
    }
    $data = [
        'hasprogramme' => true,
        'percent' => $summary['percent'],
        'months' => $months,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/parcours', $data);
echo $OUTPUT->footer();
