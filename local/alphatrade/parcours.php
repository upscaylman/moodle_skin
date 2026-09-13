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
 * "Mon parcours": the 3-month programme grouped by month (wireframe v3 screen 2).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/parcours.php', 'parcours', get_string('parcours', 'local_alphatrade'));

$data = ['hasprogramme' => false];
$programme = programme::for_user($USER->id);
if ($programme) {
    $summary = $programme->get_summary();
    $data = [
        'hasprogramme' => true,
        'coursename' => format_string($programme->get_course_record()->fullname),
        'percent' => $summary['percent'],
        'week' => get_string('weekcounter', 'local_alphatrade', ['week' => $summary['week'], 'weeks' => $summary['weeks']]),
        'lessons' => get_string('lessonsdonecounter', 'local_alphatrade',
            ['done' => $summary['lessonsdone'], 'total' => $summary['lessonstotal']]),
        'months' => array_map(function($month) {
            foreach ($month['modules'] as &$module) {
                $module['meta'] = get_string('modulemeta', 'local_alphatrade',
                    ['lessons' => $module['lessoncount'], 'percent' => $module['percent']]);
            }
            unset($module);
            return $month;
        }, $programme->get_months()),
        'projecturl' => (new moodle_url('/local/alphatrade/project.php'))->out(false),
        'certificateurl' => (new moodle_url('/local/alphatrade/certificate.php'))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/parcours', $data);
echo $OUTPUT->footer();
