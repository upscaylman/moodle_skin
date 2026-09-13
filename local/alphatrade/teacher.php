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
 * Trainer space: key figures, pedagogical alerts, students' progress, students' backtests.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\backtest;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

$view = optional_param('view', '', PARAM_ALPHA);
$view = $view === 'backtests' ? 'backtests' : '';

page::setup('/local/alphatrade/teacher.php', $view ? 'studentbacktests' : 'teacher', get_string('teacherspace', 'local_alphatrade'),
    $view ? ['view' => $view] : []);

$course = programme::get_course();
if (!$course) {
    require_capability('moodle/site:config', context_system::instance());
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_alphatrade']), get_string('noprogramme', 'local_alphatrade'));
}
$context = context_course::instance($course->id);
require_capability('local/alphatrade:viewteacher', $context);

$students = get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.*', 'u.lastname ASC, u.firstname ASC', 0, 500, true);

// Students' backtests.
if ($view === 'backtests') {
    $rows = [];
    if ($students) {
        list($insql, $params) = $DB->get_in_or_equal(array_keys($students));
        $strategies = $DB->get_records_select('local_alphatrade_strategy', "userid $insql", $params, 'timemodified DESC');
        $results = backtest::get_results_for(array_keys($strategies));
        foreach ($strategies as $strategy) {
            $card = backtest::export_card($strategy, $results[$strategy->id]);
            $card['student'] = fullname($students[$strategy->userid]);
            $card['journalurl'] = (new moodle_url('/local/alphatrade/journal.php', ['userid' => $strategy->userid]))->out(false);
            $card['enough'] = $card['trades'] >= (int) get_config('local_alphatrade', 'minbacktesttrades');
            $rows[] = $card;
        }
    }
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/teacher_backtests', [
        'rows' => $rows,
        'hasrows' => !empty($rows),
        'backurl' => (new moodle_url('/local/alphatrade/teacher.php'))->out(false),
        'minimum' => get_string('tradesminimum', 'local_alphatrade', (int) get_config('local_alphatrade', 'minbacktesttrades')),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$now = time();
$inactivedays = max(1, (int) get_config('local_alphatrade', 'inactivedays'));
$lastaccess = $DB->get_records_menu('user_lastaccess', ['courseid' => $course->id], '', 'userid, timeaccess');

// Failed quiz attempts (2 or more below the pass grade).
$failures = [];
$sql = "SELECT qa.userid, q.id AS quizid, q.name, COUNT(1) AS failures
          FROM {quiz_attempts} qa
          JOIN {quiz} q ON q.id = qa.quiz
          JOIN {grade_items} gi ON gi.iteminstance = q.id AND gi.itemmodule = 'quiz' AND gi.itemtype = 'mod'
               AND gi.courseid = q.course
         WHERE q.course = :courseid AND qa.state = 'finished' AND gi.gradepass > 0 AND q.sumgrades > 0
               AND qa.sumgrades IS NOT NULL AND (qa.sumgrades / q.sumgrades * q.grade) < gi.gradepass
      GROUP BY qa.userid, q.id, q.name
        HAVING COUNT(1) >= 2";
$rs = $DB->get_recordset_sql($sql, ['courseid' => $course->id]);
foreach ($rs as $record) {
    $failures[$record->userid][] = $record;
}
$rs->close();

// Work to correct: assignments waiting for a grade + chart analyses.
$sql = "SELECT COUNT(1)
          FROM {assign_submission} s
          JOIN {assign} a ON a.id = s.assignment
     LEFT JOIN {assign_grades} g ON g.assignment = s.assignment AND g.userid = s.userid AND g.attemptnumber = s.attemptnumber
         WHERE a.course = :courseid AND s.latest = 1 AND s.status = :status
               AND (g.id IS NULL OR g.grade IS NULL OR g.grade < 0 OR g.timemodified < s.timemodified)";
$tograde = $DB->count_records_sql($sql, ['courseid' => $course->id, 'status' => 'submitted'])
    + $DB->count_records('local_alphatrade_analysis', ['status' => 'submitted']);

$weeks = max(1, (int) get_config('local_alphatrade', 'programmeweeks'));
$stats = ['enrolled' => count($students), 'active' => 0, 'late' => 0, 'blocked' => 0];
$alerts = [];
$rows = [];

foreach ($students as $student) {
    $programme = new programme($course, $student->id);
    $summary = $programme->get_summary();
    $current = $programme->get_current_module();
    $access = (int) ($lastaccess[$student->id] ?? 0);
    $idle = $access ? (int) floor(($now - $access) / DAYSECS) : null;
    $name = fullname($student);
    $profileurl = (new moodle_url('/user/view.php', ['id' => $student->id, 'course' => $course->id]))->out(false);

    $expected = (int) floor(100 * ($summary['week'] - 1) / $weeks);
    $islate = !$summary['iscomplete'] && $summary['percent'] + 15 < $expected;
    $isblocked = !$summary['iscomplete'] && ($idle === null || $idle >= $inactivedays);
    $isactive = $idle !== null && $idle < 7;

    $stats['active'] += $isactive ? 1 : 0;
    $stats['late'] += $islate ? 1 : 0;
    $stats['blocked'] += $isblocked ? 1 : 0;

    if ($isblocked) {
        $alerts[] = [
            'level' => 'danger', 'icon' => 'ph-clock-countdown', 'student' => $name, 'url' => $profileurl,
            'text' => $idle === null ? get_string('alert_neveraccessed', 'local_alphatrade')
                : get_string('alert_inactive', 'local_alphatrade', $idle),
            'sort' => 0,
        ];
    }
    foreach ($failures[$student->id] ?? [] as $failure) {
        $alerts[] = [
            'level' => 'warning', 'icon' => 'ph-exam', 'student' => $name, 'url' => $profileurl,
            'text' => get_string('alert_quizfailures', 'local_alphatrade',
                ['count' => $failure->failures, 'quiz' => format_string($failure->name)]),
            'sort' => 1,
        ];
    }
    if ($current && $current['trackeddone'] == 0 && $idle !== null && $idle >= 3) {
        $previous = null;
        foreach ($programme->get_modules() as $module) {
            if ($module['sectionnum'] == $current['sectionnum']) {
                break;
            }
            $previous = $module;
        }
        if ($previous && $previous['isdone']) {
            $alerts[] = [
                'level' => 'info', 'icon' => 'ph-path', 'student' => $name, 'url' => $profileurl,
                'text' => get_string('alert_notstarted', 'local_alphatrade',
                    ['done' => $previous['name'], 'next' => $current['name']]),
                'sort' => 2,
            ];
        }
    }

    $rows[] = [
        'name' => $name,
        'url' => $profileurl,
        'percent' => $summary['percent'],
        'module' => $current ? get_string('modulelabel', 'local_alphatrade', $current['number']) . ' - ' . $current['name']
            : ($summary['iscomplete'] ? get_string('programmedone', 'local_alphatrade') : '-'),
        'lastaccess' => $access ? userdate($access, get_string('strftimedatefullshort', 'langconfig')) : get_string('never'),
        'isblocked' => $isblocked,
        'islate' => $islate && !$isblocked,
        'isok' => !$isblocked && !$islate,
        'journalurl' => (new moodle_url('/local/alphatrade/journal.php', ['userid' => $student->id]))->out(false),
    ];
}

usort($alerts, function($a, $b) {
    return $a['sort'] <=> $b['sort'];
});

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/teacher', [
    'coursename' => format_string($course->fullname),
    'kpis' => [
        ['icon' => 'ph-users', 'label' => get_string('kpi_enrolled', 'local_alphatrade'), 'value' => $stats['enrolled'], 'class' => ''],
        ['icon' => 'ph-lightning', 'label' => get_string('kpi_active', 'local_alphatrade'), 'value' => $stats['active'], 'class' => ''],
        ['icon' => 'ph-hourglass-medium', 'label' => get_string('kpi_late', 'local_alphatrade'), 'value' => $stats['late'],
            'class' => $stats['late'] ? 'text-warning' : ''],
        ['icon' => 'ph-check-square-offset', 'label' => get_string('kpi_tograde', 'local_alphatrade'), 'value' => $tograde,
            'class' => '', 'url' => (new moodle_url('/local/alphatrade/reviews.php'))->out(false)],
        ['icon' => 'ph-warning-octagon', 'label' => get_string('kpi_blocked', 'local_alphatrade'), 'value' => $stats['blocked'],
            'class' => $stats['blocked'] ? 'text-danger' : ''],
    ],
    'alerts' => array_slice($alerts, 0, 30),
    'hasalerts' => !empty($alerts),
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'backtestsurl' => (new moodle_url('/local/alphatrade/teacher.php', ['view' => 'backtests']))->out(false),
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    'participantsurl' => (new moodle_url('/user/index.php', ['id' => $course->id]))->out(false),
]);
echo $OUTPUT->footer();
