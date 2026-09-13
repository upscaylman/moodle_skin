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
 * End of programme certification: conditions checklist and certificate download
 * (the certificate itself is a Moodle activity, e.g. mod_customcert, configured in the settings).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

use local_alphatrade\local\activity;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/certificate.php', 'profile', get_string('certification', 'local_alphatrade'));

$programme = programme::for_user($USER->id);
$conditions = [];
$alldone = false;
$downloadurl = '';

if ($programme) {
    $summary = $programme->get_summary();
    $project = $programme->get_project_module();

    // Challenges of the practice course.
    $challengesdone = true;
    $practiceid = (int) get_config('local_alphatrade', 'practicecourse');
    if ($practiceid && $DB->record_exists('course', ['id' => $practiceid])) {
        $practice = get_course($practiceid);
        $modinfo = get_fast_modinfo($practice, $USER->id);
        $completion = new completion_info($practice);
        foreach ($modinfo->sections[(int) get_config('local_alphatrade', 'challengessection')] ?? [] as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            if (!$cm->url || $completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                continue;
            }
            $state = $completion->get_data($cm, false, $USER->id)->completionstate;
            $challengesdone = $challengesdone && in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]);
        }
    }

    $mintrades = (int) get_config('local_alphatrade', 'minbacktesttrades');
    $trades = activity::count_backtested_trades($USER->id);

    $checks = [
        'cond_lessons' => [$summary['lessonstotal'] > 0 && $summary['lessonsdone'] == $summary['lessonstotal'],
            $summary['lessonsdone'] . ' / ' . $summary['lessonstotal']],
        'cond_quizzes' => [$summary['quizzesdone'] == $summary['quizzestotal'],
            $summary['quizzesdone'] . ' / ' . $summary['quizzestotal']],
        'cond_challenges' => [$challengesdone, ''],
        'cond_backtest' => [$trades >= $mintrades, get_string('tradesof', 'local_alphatrade', ['done' => $trades, 'min' => $mintrades])],
        'cond_project' => [$project && $project['isdone'], $project ? $project['percent'] . ' %' : ''],
    ];
    $alldone = true;
    foreach ($checks as $key => list($done, $detail)) {
        $alldone = $alldone && $done;
        $conditions[] = [
            'label' => get_string($key, 'local_alphatrade'),
            'detail' => $detail,
            'class' => $done ? 'is-done' : 'is-todo',
            'icon' => $done ? 'ph-fill ph-check-circle' : 'ph ph-circle',
        ];
    }

    $cmid = (int) get_config('local_alphatrade', 'certificatecm');
    if ($alldone && $cmid && ($cmrecord = $DB->get_record('course_modules', ['id' => $cmid]))) {
        $cm = get_fast_modinfo($cmrecord->course, $USER->id)->get_cm($cmid);
        if ($cm->uservisible && $cm->url) {
            $downloadurl = $cm->url->out(false);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/certificate', [
    'fullname' => fullname($USER),
    'hasprogramme' => (bool) $programme,
    'conditions' => $conditions,
    'alldone' => $alldone,
    'downloadurl' => $downloadurl,
    'profileurl' => (new moodle_url('/local/alphatrade/profile.php'))->out(false),
]);
echo $OUTPUT->footer();
