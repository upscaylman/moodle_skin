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
 * Admin space (maquette "Espace Admin"): dashboard, users, courses, cohorts, reports.
 * Every action opens the native Moodle page (edit user, edit course, cohort members...).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

$view = optional_param('view', '', PARAM_ALPHA);
$view = in_array($view, ['users', 'courses', 'cohorts', 'reports', 'applications',
    'contents', 'assessments', 'certifications']) ? $view : '';
$search = trim(optional_param('q', '', PARAM_TEXT));

$keys = ['' => 'adminhome', 'users' => 'adminusers', 'courses' => 'admincourses', 'cohorts' => 'admincohorts',
    'reports' => 'adminreports', 'applications' => 'adminhome'];
page::setup('/local/alphatrade/admin.php', $keys[$view], get_string('admin_' . ($view ?: 'home'), 'local_alphatrade'),
    array_filter(['view' => $view, 'q' => $search]), get_string('sub_admin' . $view, 'local_alphatrade'));
require_capability('moodle/site:config', context_system::instance());

$studentroles = array_keys(get_archetype_roles('student'));
$teacherroles = array_merge(array_keys(get_archetype_roles('editingteacher')), array_keys(get_archetype_roles('teacher')));
$monthago = time() - 30 * DAYSECS;

/**
 * Distinct users holding one of the roles (any context).
 *
 * @param int[] $roleids
 * @param string $extra extra SQL condition on u
 * @param array $params
 * @return int
 */
function local_alphatrade_count_role_users(array $roleids, string $extra = '', array $params = []): int {
    global $DB;
    if (!$roleids) {
        return 0;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
    return (int) $DB->count_records_sql("SELECT COUNT(DISTINCT ra.userid)
                                           FROM {role_assignments} ra
                                           JOIN {user} u ON u.id = ra.userid AND u.deleted = 0
                                          WHERE ra.roleid $insql $extra", $inparams + $params);
}

/**
 * Average completion (%) of the programme course for a set of users (tracked activities).
 *
 * @param int[]|null $userids null = all enrolled users
 * @return int|null
 */
function local_alphatrade_programme_completion(?array $userids = null): ?int {
    global $DB;
    $course = programme::get_course();
    if (!$course) {
        return null;
    }
    $context = context_course::instance($course->id);
    $enrolled = array_keys(get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.id', null, 0, 0, true));
    $users = $userids === null ? $enrolled : array_values(array_intersect($enrolled, $userids));
    $tracked = $DB->count_records_select('course_modules', 'course = ? AND completion > 0 AND deletioninprogress = 0',
        [$course->id]);
    if (!$users || !$tracked) {
        return null;
    }
    list($insql, $params) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'u');
    $done = $DB->count_records_sql("SELECT COUNT(1)
                                      FROM {course_modules_completion} cmc
                                      JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                                     WHERE cm.course = :courseid AND cm.completion > 0 AND cm.deletioninprogress = 0
                                           AND cmc.completionstate IN (1, 2) AND cmc.userid $insql",
        $params + ['courseid' => $course->id]);
    return (int) round(100 * $done / ($tracked * count($users)));
}

$data = [];

if ($view === 'applications') {
    $action = optional_param('status', '', PARAM_ALPHA);
    if (in_array($action, ['contacted', 'archived']) && data_submitted() && confirm_sesskey()) {
        $DB->set_field('local_alphatrade_application', 'status', $action, ['id' => required_param('id', PARAM_INT)]);
        redirect(new moodle_url('/local/alphatrade/admin.php', ['view' => 'applications']));
    }
    $data['rows'] = [];
    foreach ($DB->get_records('local_alphatrade_application', null, 'timecreated DESC', '*', 0, 200) as $application) {
        $data['rows'][] = [
            'id' => $application->id,
            'name' => $application->fullname,
            'email' => $application->email,
            'phone' => $application->phone ?: '-',
            'level' => !empty($application->level) ? get_string('level_' . $application->level, 'local_alphatrade') : '-',
            'motivation' => $application->motivation ?: '-',
            'received' => page::ago((int) $application->timecreated),
            'isnew' => $application->status === 'new',
            'iscontacted' => $application->status === 'contacted',
            'isarchived' => $application->status === 'archived',
        ];
    }
    $data['hasrows'] = !empty($data['rows']);
    $data['action'] = (new moodle_url('/local/alphatrade/admin.php', ['view' => 'applications']))->out(false);
    $data['sesskey'] = sesskey();
}

if ($view === '') {
    $completion = local_alphatrade_programme_completion();
    $data['kpis'] = [
        ['label' => get_string('kpi_users', 'local_alphatrade'),
            'value' => $DB->count_records_select('user', 'deleted = 0 AND id <> ?', [$CFG->siteguest])],
        ['label' => get_string('kpi_active', 'local_alphatrade'),
            'value' => local_alphatrade_count_role_users($studentroles, 'AND u.lastaccess > :since', ['since' => $monthago])],
        ['label' => get_string('kpi_teachers', 'local_alphatrade'), 'value' => local_alphatrade_count_role_users($teacherroles)],
        ['label' => get_string('kpi_globalcompletion', 'local_alphatrade'), 'value' => $completion === null ? '-' : $completion . ' %'],
    ];

    // Enrolments of the last 6 months.
    $months = [];
    $start = strtotime(date('Y-m-01 00:00:00', strtotime('-5 months', strtotime(date('Y-m-01')))));
    for ($i = 0; $i < 6; $i++) {
        $from = strtotime("+$i months", $start);
        $to = strtotime('+1 month', $from);
        $months[] = [
            'label' => core_text::strtotitle(rtrim(userdate($from, '%b'), '.')),
            'count' => $DB->count_records_select('user_enrolments', 'timecreated >= ? AND timecreated < ?', [$from, $to]),
        ];
    }
    $max = max(1, max(array_column($months, 'count')));
    foreach ($months as $i => &$month) {
        $height = (int) round(140 * $month['count'] / $max);
        $month['x'] = 10 + $i * 80;
        $month['y'] = 160 - $height;
        $month['height'] = $height;
        $month['islast'] = $i === 5;
    }
    unset($month);
    $data['months'] = $months;

    // New users.
    $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
    $newusers = $DB->get_records_sql("SELECT u.id, u.timecreated, $userfields
                                        FROM {user} u
                                       WHERE u.deleted = 0 AND u.id <> :guest AND u.id <> :admin
                                    ORDER BY u.timecreated DESC", ['guest' => $CFG->siteguest, 'admin' => get_admin()->id], 0, 5);
    $data['newusers'] = [];
    foreach ($newusers as $user) {
        $cohort = $DB->get_field_sql("SELECT c.name FROM {cohort} c JOIN {cohort_members} m ON m.cohortid = c.id
                                       WHERE m.userid = ? ORDER BY m.timeadded DESC", [$user->id], IGNORE_MULTIPLE);
        $data['newusers'][] = [
            'name' => fullname($user),
            'url' => (new moodle_url('/user/editadvanced.php', ['id' => $user->id]))->out(false),
            'tag' => $cohort ? format_string($cohort) : page::ago((int) $user->timecreated),
        ];
    }
    $data['hasnewusers'] = !empty($data['newusers']);
    $data['newapplications'] = $DB->count_records('local_alphatrade_application', ['status' => 'new']);
    $data['applicationsurl'] = (new moodle_url('/local/alphatrade/admin.php', ['view' => 'applications']))->out(false);
}

if ($view === 'users') {
    $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
    $where = 'u.deleted = 0 AND u.id <> :guest';
    $params = ['guest' => $CFG->siteguest];
    if ($search !== '') {
        $where .= ' AND (' . $DB->sql_like($DB->sql_fullname('u.firstname', 'u.lastname'), ':q1', false, false)
            . ' OR ' . $DB->sql_like('u.email', ':q2', false, false) . ')';
        $params['q1'] = '%' . $DB->sql_like_escape($search) . '%';
        $params['q2'] = $params['q1'];
    }
    $users = $DB->get_records_sql("SELECT u.id, u.email, u.suspended, u.auth, $userfields
                                     FROM {user} u
                                    WHERE $where
                                 ORDER BY u.timecreated DESC", $params, 0, 100);
    $admins = array_keys(get_admins());
    $data['rows'] = [];
    foreach ($users as $user) {
        if (in_array($user->id, $admins)) {
            $role = ['label' => get_string('role_admin', 'local_alphatrade'), 'class' => 'at-tag-accent'];
        } else if ($teacherroles && $DB->record_exists_select('role_assignments', 'userid = ? AND roleid IN (' .
                implode(',', array_map('intval', $teacherroles)) . ')', [$user->id])) {
            $role = ['label' => get_string('role_teacher', 'local_alphatrade'), 'class' => 'at-tag-outline'];
        } else {
            $role = ['label' => get_string('role_student', 'local_alphatrade'), 'class' => 'at-tag-neutral'];
        }
        $cohort = $DB->get_field_sql("SELECT c.name FROM {cohort} c JOIN {cohort_members} m ON m.cohortid = c.id
                                       WHERE m.userid = ? ORDER BY m.timeadded DESC", [$user->id], IGNORE_MULTIPLE);
        $data['rows'][] = [
            'name' => fullname($user),
            'email' => $user->email,
            'role' => $role,
            'cohort' => $cohort ? format_string($cohort) : '-',
            'issuspended' => $user->suspended || $user->auth === 'nologin',
            'editurl' => (new moodle_url('/user/editadvanced.php', ['id' => $user->id]))->out(false),
        ];
    }
    $data['hasrows'] = !empty($data['rows']);
    $data['addurl'] = (new moodle_url('/user/editadvanced.php', ['id' => -1]))->out(false);
    $data['action'] = (new moodle_url('/local/alphatrade/admin.php'))->out(false);
    $data['q'] = $search;
}

if ($view === 'courses') {
    $courses = $DB->get_records_select('course', 'id <> ?', [SITEID], 'sortorder ASC', 'id, fullname, category, visible', 0, 200);
    $data['courses'] = [];
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        $category = core_course_category::get($course->category, IGNORE_MISSING, true);
        $data['courses'][] = [
            'name' => format_string($course->fullname, true, ['context' => $context]),
            'meta' => get_string('coursemeta', 'local_alphatrade', [
                'category' => $category ? $category->get_formatted_name() : '-',
                'count' => count_enrolled_users($context),
                'state' => get_string($course->visible ? 'published' : 'draft', 'local_alphatrade'),
            ]),
            'editurl' => (new moodle_url('/course/edit.php', ['id' => $course->id]))->out(false),
            'viewurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'copyurl' => (new moodle_url('/backup/copy.php', ['id' => $course->id]))->out(false),
            'isvisible' => (bool) $course->visible,
        ];
    }
    $data['hascourses'] = !empty($data['courses']);
    $data['addurl'] = (new moodle_url('/course/edit.php', ['category' => core_course_category::get_default()->id]))->out(false);
}

if ($view === 'cohorts') {
    $cohorts = $DB->get_records('cohort', null, 'timecreated DESC', 'id, name, timecreated', 0, 200);
    $data['rows'] = [];
    foreach ($cohorts as $cohort) {
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$cohort->id]);
        $completion = $members ? local_alphatrade_programme_completion($members) : null;
        $data['rows'][] = [
            'name' => format_string($cohort->name),
            'count' => count($members),
            'start' => userdate($cohort->timecreated, '%d/%m/%Y'),
            'completion' => $completion === null ? '-' : $completion . ' %',
            'manageurl' => (new moodle_url('/cohort/assign.php', ['id' => $cohort->id]))->out(false),
        ];
    }
    $data['hasrows'] = !empty($data['rows']);
    $data['addurl'] = (new moodle_url('/cohort/edit.php', ['contextid' => context_system::instance()->id]))->out(false);
}

if ($view === 'reports') {
    $course = programme::get_course();
    $students = $course ? get_enrolled_users(context_course::instance($course->id), 'mod/quiz:attempt', 0, 'u.id, u.lastaccess',
        null, 0, 0, true) : [];
    $total = count($students);
    $weekactive = count(array_filter($students, function($user) {
        return $user->lastaccess > time() - 7 * DAYSECS;
    }));
    $dropped = count(array_filter($students, function($user) use ($monthago) {
        return $user->lastaccess < $monthago;
    }));
    $quizavg = $course ? $DB->get_field_sql("SELECT AVG(g.grade / q.grade * 20)
                                               FROM {quiz_grades} g
                                               JOIN {quiz} q ON q.id = g.quiz
                                              WHERE q.course = ? AND q.grade > 0", [$course->id]) : null;
    $data['kpis'] = [
        ['label' => get_string('kpi_activeweek', 'local_alphatrade'), 'value' => $total ? $weekactive . ' / ' . $total : '-'],
        ['label' => get_string('kpi_dropout', 'local_alphatrade'), 'value' => $total ? (int) round(100 * $dropped / $total) . ' %' : '-'],
        ['label' => get_string('kpi_quizavg', 'local_alphatrade'), 'value' => $quizavg === null || $quizavg === false ? '-'
            : format_float((float) $quizavg, 1) . '/20'],
    ];

    $data['rows'] = [];
    if ($course && $students) {
        $done = [];
        foreach (array_keys($students) as $userid) {
            foreach ((new programme($course, $userid))->get_modules() as $module) {
                $done[$module['sectionnum']] = ($done[$module['sectionnum']] ?? 0) + ($module['isdone'] ? 1 : 0);
            }
        }
        $modinfo = get_fast_modinfo($course, -1);
        foreach ((new programme($course, array_key_first($students)))->get_modules() as $module) {
            $quizids = [];
            foreach ($modinfo->sections[$module['sectionnum']] ?? [] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                if ($cm->modname === 'quiz') {
                    $quizids[] = $cm->instance;
                }
            }
            $avg = null;
            if ($quizids) {
                list($insql, $params) = $DB->get_in_or_equal($quizids);
                $avg = $DB->get_field_sql("SELECT AVG(g.grade / q.grade * 20) FROM {quiz_grades} g JOIN {quiz} q ON q.id = g.quiz
                                            WHERE q.id $insql AND q.grade > 0", $params);
            }
            $data['rows'][] = [
                'name' => get_string('modulelabel', 'local_alphatrade', $module['number']) . ' - ' . $module['name'],
                'count' => $total,
                'completion' => (int) round(100 * ($done[$module['sectionnum']] ?? 0) / $total) . ' %',
                'average' => $avg === null || $avg === false ? '-' : format_float((float) $avg, 1),
            ];
        }
    }
    $data['hasrows'] = !empty($data['rows']);
}

// Contenus : tout ce qui est publié dans le programme, module par module.
if ($view === 'contents') {
    $course = programme::get_course();
    $data['rows'] = [];
    if ($course) {
        $modinfo = get_fast_modinfo($course, -1);
        $counts = ['page' => 0, 'quiz' => 0, 'assign' => 0, 'resource' => 0, 'url' => 0];
        foreach ($modinfo->get_section_info_all() as $sectioninfo) {
            if ($sectioninfo->section == 0) {
                continue;
            }
            $items = [];
            foreach ($modinfo->sections[$sectioninfo->section] ?? [] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                if (!empty($cm->deletioninprogress) || $cm->modname === 'label') {
                    continue;
                }
                $items[] = $cm;
                if (isset($counts[$cm->modname])) {
                    $counts[$cm->modname]++;
                }
            }
            $hidden = count(array_filter($items, function($cm) {
                return !$cm->visible;
            }));
            $data['rows'][] = [
                'name' => get_section_name($course, $sectioninfo),
                'count' => count($items),
                'completion' => $hidden ? get_string('contents_draft', 'local_alphatrade', $hidden)
                    : get_string('contents_published', 'local_alphatrade'),
                'average' => '',
                'url' => (new moodle_url('/local/alphatrade/module.php', ['section' => $sectioninfo->section]))->out(false),
            ];
        }
        $data['kpis'] = [
            ['label' => get_string('contents_lessons', 'local_alphatrade'), 'value' => $counts['page']],
            ['label' => get_string('contents_quizzes', 'local_alphatrade'), 'value' => $counts['quiz']],
            ['label' => get_string('contents_assigns', 'local_alphatrade'), 'value' => $counts['assign']],
            ['label' => get_string('contents_medias', 'local_alphatrade'), 'value' => $counts['resource'] + $counts['url']],
        ];
    }
    $data['hasrows'] = !empty($data['rows']);
    $data['addurl'] = (new moodle_url('/local/alphatrade/create.php'))->out(false);
}

// Évaluations : quiz et devoirs notés du programme, avec la participation et la moyenne.
if ($view === 'assessments') {
    $course = programme::get_course();
    $data['rows'] = [];
    if ($course) {
        $context = context_course::instance($course->id);
        $students = count(get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.id', null, 0, 0, true));
        $items = grade_item::fetch_all(['courseid' => $course->id, 'itemtype' => 'mod']) ?: [];
        core_collator::asort_objects_by_property($items, 'sortorder', core_collator::SORT_NUMERIC);
        foreach ($items as $item) {
            $grades = grade_grade::fetch_all(['itemid' => $item->id]) ?: [];
            $values = [];
            foreach ($grades as $grade) {
                if ($grade->finalgrade !== null) {
                    $values[] = (float) $grade->finalgrade;
                }
            }
            $average = $values ? array_sum($values) / count($values) : null;
            $data['rows'][] = [
                'name' => $item->get_name(),
                'count' => count($values) . ($students ? ' / ' . $students : ''),
                'completion' => $item->gradepass > 0 ? format_float((float) $item->gradepass, 1) . ' / '
                    . format_float((float) $item->grademax, 1) : '-',
                'average' => $average === null ? '-' : format_float($average, 1) . ' / '
                    . format_float((float) $item->grademax, 1),
            ];
        }
        $data['kpis'] = [
            ['label' => get_string('assess_count', 'local_alphatrade'), 'value' => count($items)],
            ['label' => get_string('assess_students', 'local_alphatrade'), 'value' => $students],
        ];
    }
    $data['hasrows'] = !empty($data['rows']);
    $data['addurl'] = (new moodle_url('/local/alphatrade/create.php'))->out(false);
}

// Certifications : qui est éligible (modules terminés + projet rendu), qui ne l'est pas encore.
if ($view === 'certifications') {
    $course = programme::get_course();
    $data['rows'] = [];
    $eligible = 0;
    if ($course) {
        $context = context_course::instance($course->id);
        foreach (get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.*', 'u.lastname', 0, 0, true) as $user) {
            $studentprogramme = new programme($course, $user->id);
            $summary = $studentprogramme->get_summary();
            $iscomplete = $summary['iscomplete'];
            $eligible += $iscomplete ? 1 : 0;
            $data['rows'][] = [
                'name' => fullname($user),
                'count' => $summary['modulesdone'] . ' / ' . $summary['modulestotal'],
                'completion' => $summary['percent'] . ' %',
                'average' => $iscomplete ? get_string('cert_eligible', 'local_alphatrade')
                    : get_string('cert_inprogress', 'local_alphatrade'),
                'url' => (new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]))->out(false),
            ];
        }
        $data['kpis'] = [
            ['label' => get_string('cert_eligiblecount', 'local_alphatrade'), 'value' => $eligible],
            ['label' => get_string('cert_studentcount', 'local_alphatrade'), 'value' => count($data['rows'])],
        ];
    }
    $data['hasrows'] = !empty($data['rows']);
}

$data['isdashboard'] = $view === '';
$data['isusers'] = $view === 'users';
$data['iscourses'] = $view === 'courses';
$data['iscohorts'] = $view === 'cohorts';
$data['isreports'] = $view === 'reports';
$data['isapplications'] = $view === 'applications';
$data['iscontents'] = $view === 'contents';
$data['isassessments'] = $view === 'assessments';
$data['iscertifications'] = $view === 'certifications';

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/admin', $data);
echo $OUTPUT->footer();
