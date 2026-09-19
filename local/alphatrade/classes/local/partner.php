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

namespace local_alphatrade\local;

use context_course;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Data of the partner space. The partner answers three questions and nothing else:
 * who are my students, where are they, who needs attention. Read only by construction:
 * this class never writes and only exposes progress, activity and authorised results.
 *
 * Perimeter: the students of the cohorts the partner is assigned to, or - when no cohort
 * is assigned - the students of the programme the partner can see.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class partner {

    /**
     * Everything the partner screens need.
     *
     * @param int $partnerid
     * @param string $view
     * @return array
     */
    public static function export(int $partnerid, string $view): array {
        $course = programme::get_course();
        if (!$course) {
            return ['hasstudents' => false, 'students' => []];
        }
        $students = self::students($partnerid, $course);
        $inactivedays = max(1, (int) get_config('local_alphatrade', 'inactivedays'));

        $watch = array_values(array_filter($students, function($student) {
            return $student['towatch'];
        }));
        $done = array_values(array_filter($students, function($student) {
            return $student['percent'] >= 100;
        }));
        $active = array_values(array_filter($students, function($student) {
            return !$student['towatch'] && $student['percent'] < 100;
        }));
        $percents = array_column($students, 'percent');

        return [
            'coursename' => format_string($course->fullname),
            'courseurl' => (new moodle_url('/local/alphatrade/parcours.php'))->out(false),
            'students' => $students,
            'hasstudents' => !empty($students),
            'countstudents' => count($students),
            'active' => $active,
            'hasactive' => !empty($active),
            'countactive' => count($active),
            'watch' => $watch,
            'haswatch' => !empty($watch),
            'countwatch' => count($watch),
            'done' => $done,
            'countdone' => count($done),
            'average' => $percents ? (int) round(array_sum($percents) / count($percents)) : 0,
            'inactivedays' => $inactivedays,
            'modules' => self::modules($course, $students),
        ];
    }

    /**
     * Students of the partner perimeter, with progress, last access and grade.
     *
     * @param int $partnerid
     * @param stdClass $course
     * @return array
     */
    protected static function students(int $partnerid, stdClass $course): array {
        global $DB;
        $context = context_course::instance($course->id);
        $inactivedays = max(1, (int) get_config('local_alphatrade', 'inactivedays'));
        $cohortids = self::cohort_ids($partnerid);

        $users = get_enrolled_users($context, 'mod/assign:submit', 0, 'u.*', 'u.lastname, u.firstname');
        $rows = [];
        foreach ($users as $user) {
            if ($cohortids && !self::in_cohorts($user->id, $cohortids)) {
                continue;
            }
            $programme = new programme($course, $user->id);
            $summary = $programme->get_summary();
            $lastaccess = (int) $DB->get_field('user_lastaccess', 'timeaccess',
                ['userid' => $user->id, 'courseid' => $course->id]);
            $days = $lastaccess ? (int) floor((time() - $lastaccess) / DAYSECS) : null;
            $current = $programme->get_current_module();
            $rows[] = [
                'id' => $user->id,
                'fullname' => fullname($user),
                'initials' => \core_text::strtoupper(\core_text::substr($user->firstname, 0, 1)
                    . \core_text::substr($user->lastname, 0, 1)),
                'email' => $user->email,
                'percent' => $summary['percent'],
                'modulesdone' => $summary['modulesdone'],
                'modulestotal' => $summary['modulestotal'],
                'current' => $current ? $current['name'] : '',
                'hascurrent' => !empty($current),
                'lastaccess' => $lastaccess ? userdate($lastaccess, get_string('strftimedatefullshort', 'langconfig'))
                    : get_string('never'),
                'days' => $days,
                'towatch' => $days === null || $days >= $inactivedays,
                'profileurl' => (new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]))->out(false),
            ];
        }
        return $rows;
    }

    /**
     * Cohorts the partner belongs to: they define the perimeter of the students followed.
     *
     * @param int $partnerid
     * @return int[]
     */
    protected static function cohort_ids(int $partnerid): array {
        global $DB;
        return array_keys($DB->get_records('cohort_members', ['userid' => $partnerid], '', 'cohortid'));
    }

    /**
     * Is the user a member of one of these cohorts?
     *
     * @param int $userid
     * @param int[] $cohortids
     * @return bool
     */
    protected static function in_cohorts(int $userid, array $cohortids): bool {
        global $DB;
        list($insql, $params) = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        return $DB->record_exists_select('cohort_members', "userid = :userid AND cohortid {$insql}", $params);
    }

    /**
     * Group progress per module: how many of the followed students completed each module.
     *
     * @param stdClass $course
     * @param array $students
     * @return array
     */
    protected static function modules(stdClass $course, array $students): array {
        if (!$students) {
            return [];
        }
        $totals = [];
        foreach ($students as $student) {
            $programme = new programme($course, $student['id']);
            foreach ($programme->get_modules() as $module) {
                $key = $module['sectionnum'];
                if (!isset($totals[$key])) {
                    $totals[$key] = ['number' => $module['number'], 'name' => $module['name'],
                        'done' => 0, 'sum' => 0, 'count' => 0];
                }
                $totals[$key]['done'] += $module['isdone'] ? 1 : 0;
                $totals[$key]['sum'] += $module['percent'];
                $totals[$key]['count']++;
            }
        }
        $rows = [];
        foreach ($totals as $total) {
            $rows[] = [
                'number' => $total['number'],
                'name' => $total['name'],
                'done' => $total['done'],
                'count' => $total['count'],
                'percent' => $total['count'] ? (int) round($total['sum'] / $total['count']) : 0,
            ];
        }
        return $rows;
    }
}
