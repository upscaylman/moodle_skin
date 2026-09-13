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
 * Practice Lab (maquette Portail Etudiant, screen "Pratique"). Case studies and challenges are
 * activities of the "Pratique" course (one section each).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

$view = optional_param('view', '', PARAM_ALPHA);
$view = in_array($view, ['cases', 'challenges']) ? $view : '';

page::setup('/local/alphatrade/practice.php', 'practice', get_string('nav_practice', 'theme_alphatrade'),
    $view ? ['view' => $view] : [], get_string('sub_practice', 'local_alphatrade'));

$baseurl = new moodle_url('/local/alphatrade/practice.php');

if (!$view) {
    $data = [
        'cards' => [
            ['icon' => 'ph-chart-line-up', 'title' => get_string('practice_charts', 'local_alphatrade'),
                'text' => get_string('practice_charts_desc', 'local_alphatrade'), 'cta' => get_string('start', 'local_alphatrade'),
                'url' => (new moodle_url('/local/alphatrade/charts.php'))->out(false), 'primary' => true],
            ['icon' => 'ph-brain', 'title' => get_string('practice_cases', 'local_alphatrade'),
                'text' => get_string('practice_cases_desc', 'local_alphatrade'), 'cta' => get_string('start', 'local_alphatrade'),
                'url' => (new moodle_url($baseurl, ['view' => 'cases']))->out(false), 'primary' => false],
            ['icon' => 'ph-trophy', 'title' => get_string('practice_challenges', 'local_alphatrade'),
                'text' => get_string('practice_challenges_desc', 'local_alphatrade'), 'cta' => get_string('seechallenges', 'local_alphatrade'),
                'url' => (new moodle_url($baseurl, ['view' => 'challenges']))->out(false), 'primary' => false],
        ],
    ];
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/practice', $data);
    echo $OUTPUT->footer();
    exit;
}

$items = [];
$courseid = (int) get_config('local_alphatrade', 'practicecourse');
$sectionnum = (int) get_config('local_alphatrade', $view === 'cases' ? 'casessection' : 'challengessection');
if ($courseid && $DB->record_exists('course', ['id' => $courseid])) {
    $course = get_course($courseid);
    $modinfo = get_fast_modinfo($course, $USER->id);
    $completion = new completion_info($course);
    $number = 0;
    foreach ($modinfo->sections[$sectionnum] ?? [] as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if (!$cm->url || (!$cm->uservisible && empty($cm->availableinfo)) || !empty($cm->deletioninprogress)) {
            continue;
        }
        $isdone = false;
        if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
            $state = $completion->get_data($cm, false, $USER->id)->completionstate;
            $isdone = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]);
        }
        $status = !$cm->uservisible ? 'locked' : ($isdone ? 'done' : 'todo');
        $number++;
        $items[] = [
            'number' => sprintf('%02d', $number),
            'name' => $cm->get_formatted_name(),
            'url' => $cm->uservisible ? $cm->url->out(false) : '',
            'islocked' => !$cm->uservisible,
            'iscurrent' => false,
            'iconclass' => programme::maquette_icon($status),
            'statusclass' => programme::maquette_status_class($status),
        ];
    }
}

$title = get_string($view === 'cases' ? 'practice_cases' : 'practice_challenges', 'local_alphatrade');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/practice_list', [
    'backurl' => $baseurl->out(false),
    'title' => $title,
    'lead' => get_string($view === 'cases' ? 'practice_cases_desc' : 'practice_challenges_desc', 'local_alphatrade'),
    'rows' => $items,
    'hasrows' => !empty($items),
]);
echo $OUTPUT->footer();
