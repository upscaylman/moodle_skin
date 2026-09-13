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
 * Trainer space (maquette "Espace Enseignant"): dashboard, students follow-up, students' backtests.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\backtest;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;
use local_alphatrade\local\reviewqueue;
use local_alphatrade\local\stats;

$view = optional_param('view', '', PARAM_ALPHA);
$view = in_array($view, ['backtests', 'students']) ? $view : '';

$titles = [
    '' => [get_string('hello_name', 'local_alphatrade', $USER->firstname ?? ''), get_string('sub_teacher', 'local_alphatrade')],
    'students' => [get_string('students', 'local_alphatrade'), get_string('sub_teacherstudents', 'local_alphatrade')],
    'backtests' => [get_string('studentbacktests', 'local_alphatrade'), get_string('sub_teacherbacktests', 'local_alphatrade')],
];
page::setup('/local/alphatrade/teacher.php', $view === 'backtests' ? 'studentbacktests' : 'teacher', $titles[$view][0],
    $view ? ['view' => $view] : [], $titles[$view][1]);

$course = programme::get_course();
if (!$course) {
    require_capability('moodle/site:config', context_system::instance());
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_alphatrade']), get_string('noprogramme', 'local_alphatrade'));
}
$context = context_course::instance($course->id);
require_capability('local/alphatrade:viewteacher', $context);

$students = get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.*', 'u.lastname ASC, u.firstname ASC', 0, 500, true);
$minimum = (int) get_config('local_alphatrade', 'minbacktesttrades');

// Students' backtests.
if ($view === 'backtests') {
    $rows = [];
    $withbacktest = [];
    $expectancies = [];
    $winrates = [];
    $toreview = 0;
    if ($students) {
        list($insql, $params) = $DB->get_in_or_equal(array_keys($students));
        $strategies = $DB->get_records_select('local_alphatrade_strategy', "userid $insql", $params, 'timemodified DESC');
        $results = backtest::get_results_for(array_keys($strategies));
        foreach ($strategies as $strategy) {
            $computed = stats::compute($results[$strategy->id]);
            if (!$computed['trades']) {
                continue;
            }
            $isreview = $computed['expectancy'] < 0 || $computed['trades'] < $minimum;
            $withbacktest[$strategy->userid] = true;
            $expectancies[] = $computed['expectancy'];
            $winrates[] = $computed['winrate'];
            $toreview += $isreview ? 1 : 0;
            $rows[] = array_merge(backtest::export_card($strategy, $results[$strategy->id]), [
                'student' => fullname($students[$strategy->userid]),
                'isreview' => $isreview,
                'reviewlabel' => $computed['expectancy'] >= 0
                    ? get_string('samplesmall', 'local_alphatrade') : get_string('toreview', 'local_alphatrade'),
            ]);
        }
    }
    $count = count($expectancies);
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/teacher_backtests', [
        'kpis' => [
            ['label' => get_string('kpi_withbacktest', 'local_alphatrade'), 'value' => count($withbacktest)],
            ['label' => get_string('kpi_avgexpectancy', 'local_alphatrade'),
                'value' => $count ? page::format_r(array_sum($expectancies) / $count, 2, false) : '-'],
            ['label' => get_string('kpi_avgwinrate', 'local_alphatrade'),
                'value' => $count ? page::num(array_sum($winrates) / $count, 1) . ' %' : '-'],
            ['label' => get_string('toreview', 'local_alphatrade'), 'value' => $toreview, 'iswarning' => $toreview > 0],
        ],
        'rows' => $rows,
        'hasrows' => !empty($rows),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$now = time();
$inactivedays = max(1, (int) get_config('local_alphatrade', 'inactivedays'));
$lastaccess = $DB->get_records_menu('user_lastaccess', ['courseid' => $course->id], '', 'userid, timeaccess');
$weeks = max(1, (int) get_config('local_alphatrade', 'programmeweeks'));

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

$active = 0;
$percents = [];
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
    $active += $idle !== null && $idle < 7 ? 1 : 0;
    $percents[] = $summary['percent'];

    if ($isblocked) {
        $alerts[] = ['student' => $name, 'url' => $profileurl, 'sort' => 0,
            'text' => $idle === null ? get_string('alert_neveraccessed', 'local_alphatrade')
                : get_string('alert_inactive', 'local_alphatrade', $idle)];
    }
    foreach ($failures[$student->id] ?? [] as $failure) {
        $alerts[] = ['student' => $name, 'url' => $profileurl, 'sort' => 1,
            'text' => get_string('alert_quizfailures', 'local_alphatrade',
                ['count' => $failure->failures, 'quiz' => format_string($failure->name)])];
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
            $alerts[] = ['student' => $name, 'url' => $profileurl, 'sort' => 2,
                'text' => get_string('alert_notstarted', 'local_alphatrade',
                    ['done' => $previous['name'], 'next' => $current['name']])];
        }
    }

    $rows[] = [
        'name' => $name,
        'url' => $profileurl,
        'percent' => $summary['percent'],
        'module' => $current ? get_string('modulelabel', 'local_alphatrade', $current['number']) . ' - ' . $current['name']
            : ($summary['iscomplete'] ? get_string('programmedone', 'local_alphatrade') : '-'),
        'lastaccess' => $access ? page::ago($access) : get_string('never'),
        'isblocked' => $isblocked,
        'islate' => $islate && !$isblocked,
        'isok' => !$isblocked && !$islate,
        'journalurl' => (new moodle_url('/local/alphatrade/journal.php', ['userid' => $student->id]))->out(false),
    ];
}
usort($alerts, function($a, $b) {
    return $a['sort'] <=> $b['sort'];
});

// Students follow-up.
if ($view === 'students') {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/teacher_students', [
        'alerts' => array_slice($alerts, 0, 30),
        'hasalerts' => !empty($alerts),
        'rows' => $rows,
        'hasrows' => !empty($rows),
        'participantsurl' => (new moodle_url('/user/index.php', ['id' => $course->id]))->out(false),
    ]);
    echo $OUTPUT->footer();
    exit;
}

// Recent activity: last completions of the programme, then the pedagogical alerts.
$activity = [];
if ($students) {
    list($insql, $params) = $DB->get_in_or_equal(array_keys($students), SQL_PARAMS_NAMED, 'u');
    $sql = "SELECT cmc.id, cmc.userid, cmc.coursemoduleid, cmc.timemodified
              FROM {course_modules_completion} cmc
              JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
             WHERE cm.course = :courseid AND cmc.completionstate IN (1, 2) AND cmc.userid $insql
          ORDER BY cmc.timemodified DESC";
    $completions = $DB->get_records_sql($sql, $params + ['courseid' => $course->id], 0, 4);
    $modinfo = get_fast_modinfo($course, -1);
    foreach ($completions as $completion) {
        $cms = $modinfo->get_cms();
        if (empty($cms[$completion->coursemoduleid])) {
            continue;
        }
        $activity[] = [
            'html' => get_string('activity_completed', 'local_alphatrade', [
                'name' => html_writer::tag('strong', s(fullname($students[$completion->userid]))),
                'activity' => $cms[$completion->coursemoduleid]->get_formatted_name(),
            ]),
            'time' => page::ago((int) $completion->timemodified),
            'isaccent' => true,
        ];
    }
}
foreach (array_slice($alerts, 0, max(0, 6 - count($activity))) as $alert) {
    $activity[] = [
        'html' => html_writer::tag('strong', s($alert['student'])) . ' - ' . s($alert['text']),
        'time' => '',
        'isaccent' => false,
    ];
}

$reviewsurl = new moodle_url('/local/alphatrade/reviews.php');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/teacher', [
    'kpis' => [
        ['label' => get_string('kpi_active', 'local_alphatrade'), 'value' => $active,
            'url' => (new moodle_url('/local/alphatrade/teacher.php', ['view' => 'students']))->out(false)],
        ['label' => get_string('kpi_avgcompletion', 'local_alphatrade'),
            'value' => ($percents ? (int) round(array_sum($percents) / count($percents)) : 0) . ' %', 'url' => ''],
        ['label' => get_string('kpi_tograde', 'local_alphatrade'), 'value' => reviewqueue::count_pending(), 'isaccent' => true,
            'url' => $reviewsurl->out(false)],
        ['label' => get_string('kpi_unreadmessages', 'local_alphatrade'),
            'value' => \core_message\api::count_unread_conversations($USER),
            'url' => (new moodle_url('/local/alphatrade/messages.php'))->out(false)],
    ],
    'queue' => reviewqueue::items('submitted', 5),
    'reviewsurl' => $reviewsurl->out(false),
    'activity' => $activity,
    'hasactivity' => !empty($activity),
]);
echo $OUTPUT->footer();
