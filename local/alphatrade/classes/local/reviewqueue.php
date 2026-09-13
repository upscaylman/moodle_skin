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

use core_user\fields;
use moodle_url;

/**
 * Correction queue of the trainers: Moodle assignments of the programme and practice courses
 * plus chart analyses of the Practice Lab, in one list (maquette "Devoirs à corriger").
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reviewqueue {

    /**
     * Course ids whose assignments are part of the queue.
     *
     * @return int[]
     */
    public static function course_ids(): array {
        global $DB;
        $ids = [];
        $programme = programme::get_course();
        if ($programme) {
            $ids[] = (int) $programme->id;
        }
        $practice = (int) get_config('local_alphatrade', 'practicecourse');
        if ($practice && $DB->record_exists('course', ['id' => $practice])) {
            $ids[] = $practice;
        }
        return array_values(array_unique($ids));
    }

    /**
     * Number of items waiting for a correction.
     *
     * @return int
     */
    public static function count_pending(): int {
        global $DB;
        $count = $DB->count_records('local_alphatrade_analysis', ['status' => 'submitted']);
        $courseids = self::course_ids();
        if ($courseids) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
            $sql = "SELECT COUNT(1)
                      FROM {assign_submission} s
                      JOIN {assign} a ON a.id = s.assignment
                 LEFT JOIN {assign_grades} g ON g.assignment = s.assignment AND g.userid = s.userid
                           AND g.attemptnumber = s.attemptnumber
                     WHERE a.course $insql AND s.latest = 1 AND s.status = :status
                           AND (g.id IS NULL OR g.grade IS NULL OR g.grade < 0 OR g.timemodified < s.timemodified)";
            $count += $DB->count_records_sql($sql, $params + ['status' => 'submitted']);
        }
        return $count;
    }

    /**
     * Queue rows, oldest pending first (or most recent corrections first).
     *
     * @param string $filter submitted|reviewed|all
     * @param int $limit
     * @return array
     */
    public static function items(string $filter = 'submitted', int $limit = 200): array {
        global $DB;
        $userfields = fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $items = [];

        // Chart analyses.
        $where = $filter === 'all' ? '' : 'WHERE a.status = :status';
        $sql = "SELECT a.id, a.status, a.score, a.timemodified, a.userid, c.title, c.symbol, c.timeframe, $userfields
                  FROM {local_alphatrade_analysis} a
                  JOIN {local_alphatrade_chart} c ON c.id = a.chartid
                  JOIN {user} u ON u.id = a.userid
                  $where
              ORDER BY a.timemodified ASC";
        $records = $DB->get_records_sql($sql, $filter === 'all' ? [] : ['status' => $filter], 0, $limit);
        foreach ($records as $record) {
            $reviewed = $record->status === 'reviewed';
            $items[] = [
                'student' => fullname($record),
                'item' => format_string($record->title),
                'course' => get_string('practice_charts', 'local_alphatrade') . ' - ' . page::display_symbol($record->symbol)
                    . ' ' . page::timeframe_label($record->timeframe),
                'time' => (int) $record->timemodified,
                'isreviewed' => $reviewed,
                'score' => $reviewed ? (int) $record->score . ' / 100' : '',
                'url' => (new moodle_url('/local/alphatrade/review.php', ['id' => $record->id]))->out(false),
            ];
        }

        // Assignments.
        $courseids = self::course_ids();
        if ($courseids) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
            $pending = "(g.id IS NULL OR g.grade IS NULL OR g.grade < 0 OR g.timemodified < s.timemodified)";
            $condition = $filter === 'submitted' ? "AND $pending" : ($filter === 'reviewed' ? "AND NOT $pending" : '');
            $sql = "SELECT s.id, s.userid, s.timemodified, a.id AS assignid, a.name, a.course, g.grade, g.timemodified AS gradetime, a.grade AS maxgrade,
                           $userfields
                      FROM {assign_submission} s
                      JOIN {assign} a ON a.id = s.assignment
                      JOIN {user} u ON u.id = s.userid
                 LEFT JOIN {assign_grades} g ON g.assignment = s.assignment AND g.userid = s.userid
                           AND g.attemptnumber = s.attemptnumber
                     WHERE a.course $insql AND s.latest = 1 AND s.status = :status $condition
                  ORDER BY s.timemodified ASC";
            $records = $DB->get_records_sql($sql, $params + ['status' => 'submitted'], 0, $limit);
            $modinfos = [];
            foreach ($records as $record) {
                if (!isset($modinfos[$record->course])) {
                    $modinfos[$record->course] = get_fast_modinfo($record->course, -1);
                }
                $modinfo = $modinfos[$record->course];
                $instances = $modinfo->get_instances_of('assign');
                if (empty($instances[$record->assignid])) {
                    continue;
                }
                $cm = $instances[$record->assignid];
                $reviewed = $record->grade !== null && (float) $record->grade >= 0 && $record->gradetime >= $record->timemodified;
                $section = $modinfo->get_section_info($cm->sectionnum);
                $items[] = [
                    'student' => fullname($record),
                    'item' => $cm->get_formatted_name(),
                    'course' => $section ? get_section_name($record->course, $section)
                        : format_string($modinfo->get_course()->shortname),
                    'time' => (int) $record->timemodified,
                    'isreviewed' => $reviewed,
                    'score' => $reviewed && $record->maxgrade > 0
                        ? format_float((float) $record->grade, 0) . ' / ' . format_float((float) $record->maxgrade, 0) : '',
                    'url' => (new moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grader',
                        'userid' => $record->userid]))->out(false),
                ];
            }
        }

        usort($items, function($a, $b) {
            if ($a['isreviewed'] !== $b['isreviewed']) {
                return $a['isreviewed'] ? 1 : -1;
            }
            return $a['isreviewed'] ? $b['time'] <=> $a['time'] : $a['time'] <=> $b['time'];
        });
        $items = array_slice($items, 0, $limit);
        foreach ($items as &$item) {
            $item['submitted'] = page::ago($item['time']);
        }
        unset($item);
        return $items;
    }
}
