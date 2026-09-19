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

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Data of the Alpha Trade piliers that read the programme course: Vidéos (asynchronous),
 * Live (synchronous, from the course calendar) and Exercices / Mes résultats (assignments,
 * quizzes, grades). Everything comes from native Moodle records - no parallel storage.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pillars {

    /** @var string[] Activity types that carry a video. */
    const VIDEO_MODULES = ['url', 'resource', 'page', 'h5pactivity', 'lti'];

    /** @var string Hosts recognised as video platforms in a URL activity. */
    const VIDEO_HOSTS = 'youtube|youtu\.be|vimeo|dailymotion|loom|wistia|streamable|twitch|drive\.google';

    /**
     * Videos of the programme, grouped by module. A video is a URL activity pointing at a video
     * platform, a media file resource, or any activity whose name is marked as a video.
     *
     * @param int $userid
     * @return array
     */
    public static function videos(int $userid): array {
        $programme = programme::for_user($userid);
        if (!$programme) {
            return ['hasvideos' => false, 'modules' => [], 'count' => 0];
        }
        $modules = [];
        $count = 0;
        foreach ($programme->get_modules() as $module) {
            $videos = [];
            foreach ($module['items'] as $item) {
                if (!self::is_video($item)) {
                    continue;
                }
                $videos[] = [
                    'name' => $item['name'],
                    'url' => $item['url'],
                    'islocked' => $item['islocked'],
                    'isdone' => $item['isdone'],
                    'modname' => $item['modname'],
                ];
                $count++;
            }
            if ($videos) {
                $modules[] = [
                    'number' => $module['number'],
                    'name' => $module['name'],
                    'url' => $module['url'],
                    'videos' => $videos,
                    'videocount' => count($videos),
                ];
            }
        }
        return ['hasvideos' => $count > 0, 'modules' => $modules, 'count' => $count];
    }

    /**
     * Is this programme item a video?
     *
     * @param array $item
     * @return bool
     */
    protected static function is_video(array $item): bool {
        global $DB;
        if (!in_array($item['modname'], self::VIDEO_MODULES)) {
            return false;
        }
        if (preg_match('/\b(vid[ée]o|replay|webinar|screencast)\b/iu', $item['name'])) {
            return true;
        }
        if ($item['modname'] === 'url') {
            $cm = get_coursemodule_from_id('url', $item['cmid'], 0, false, IGNORE_MISSING);
            $external = $cm ? $DB->get_field('url', 'externalurl', ['id' => $cm->instance]) : '';
            return $external && preg_match('~(' . self::VIDEO_HOSTS . ')~i', $external);
        }
        return false;
    }

    /**
     * Live sessions, read from the calendar of the programme course: en cours, prochains, passés.
     * A session carries its joining link in the event description.
     *
     * @param int $userid
     * @return array
     */
    public static function lives(int $userid): array {
        global $DB;
        $course = programme::get_course();
        if (!$course) {
            return ['hascurrent' => false, 'hasnext' => false, 'haspast' => false,
                'current' => [], 'next' => [], 'past' => []];
        }
        $now = time();
        $events = $DB->get_records_select('event', 'courseid = :courseid AND visible = 1',
            ['courseid' => $course->id], 'timestart ASC');
        $current = [];
        $next = [];
        $past = [];
        foreach ($events as $event) {
            $end = $event->timestart + max(0, (int) $event->timeduration);
            $row = [
                'name' => format_string($event->name),
                'description' => format_text($event->description, $event->descriptionformat,
                    ['context' => context_course::instance($course->id)]),
                'date' => userdate($event->timestart, get_string('strftimedaydatetime', 'langconfig')),
                'time' => userdate($event->timestart, get_string('strftimetime', 'langconfig')),
                'duration' => $event->timeduration ? format_time((int) $event->timeduration) : '',
                'joinurl' => self::first_link($event->description),
                'calendarurl' => (new moodle_url('/calendar/view.php', ['view' => 'day',
                    'time' => $event->timestart]))->out(false),
            ];
            if ($event->timestart <= $now && $end >= $now) {
                $row['islive'] = true;
                $current[] = $row;
            } else if ($event->timestart > $now) {
                $next[] = $row;
            } else {
                $past[] = $row;
            }
        }
        $past = array_slice(array_reverse($past), 0, 10);
        return [
            'current' => $current, 'hascurrent' => !empty($current),
            'next' => $next, 'hasnext' => !empty($next),
            'past' => $past, 'haspast' => !empty($past),
            'calendarurl' => (new moodle_url('/calendar/view.php', ['view' => 'month',
                'course' => $course->id]))->out(false),
        ];
    }

    /**
     * First href found in a text, used as the joining link of a live session.
     *
     * @param string|null $html
     * @return string
     */
    protected static function first_link(?string $html): string {
        if ($html && preg_match('~href=["\']([^"\']+)["\']~i', $html, $matches)) {
            return $matches[1];
        }
        if ($html && preg_match('~(https?://[^\s<"\']+)~i', strip_tags($html), $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Exercises of the programme sorted the way the architecture asks: à faire, en attente de
     * correction, terminés. Covers assignments (rendus) and quizzes (évaluations).
     *
     * @param int $userid
     * @return array
     */
    public static function exercises(int $userid): array {
        global $DB;
        $programme = programme::for_user($userid);
        $course = programme::get_course();
        if (!$programme || !$course) {
            return ['todo' => [], 'waiting' => [], 'done' => [], 'hastodo' => false,
                'haswaiting' => false, 'hasdone' => false, 'counttodo' => 0];
        }
        $todo = [];
        $waiting = [];
        $done = [];
        foreach ($programme->get_modules() as $module) {
            foreach ($module['items'] as $item) {
                if (!in_array($item['modname'], ['assign', 'quiz'])) {
                    continue;
                }
                $row = [
                    'name' => $item['name'],
                    'url' => $item['url'],
                    'modulename' => $module['name'],
                    'modulenumber' => $module['number'],
                    'islocked' => $item['islocked'],
                    'isquiz' => $item['modname'] === 'quiz',
                    'icon' => $item['modname'] === 'quiz' ? 'ph-exam' : 'ph-clipboard-text',
                    'status' => '',
                    'grade' => '',
                ];
                $state = self::exercise_state($item, $userid, $course->id);
                $row = array_merge($row, $state);
                if ($state['bucket'] === 'done') {
                    $done[] = $row;
                } else if ($state['bucket'] === 'waiting') {
                    $waiting[] = $row;
                } else {
                    $todo[] = $row;
                }
            }
        }
        return [
            'todo' => $todo, 'hastodo' => !empty($todo), 'counttodo' => count($todo),
            'waiting' => $waiting, 'haswaiting' => !empty($waiting), 'countwaiting' => count($waiting),
            'done' => $done, 'hasdone' => !empty($done), 'countdone' => count($done),
        ];
    }

    /**
     * Where one exercise stands for this user.
     *
     * @param array $item
     * @param int $userid
     * @param int $courseid
     * @return array
     */
    protected static function exercise_state(array $item, int $userid, int $courseid): array {
        global $DB;
        $gradeitem = null;
        $modname = $item['modname'];
        $cm = get_coursemodule_from_id($modname, $item['cmid'], $courseid, false, IGNORE_MISSING);
        if ($cm) {
            $grades = grade_get_grades($courseid, 'mod', $modname, $cm->instance, $userid);
            $gradeitem = $grades->items[0] ?? null;
        }
        $gradetext = '';
        if ($gradeitem && isset($gradeitem->grades[$userid]) && $gradeitem->grades[$userid]->grade !== null) {
            $gradetext = format_float((float) $gradeitem->grades[$userid]->grade, 1) . ' / '
                . format_float((float) $gradeitem->grademax, 1);
        }

        if ($modname === 'assign' && $cm) {
            $submission = $DB->get_record('assign_submission', ['assignment' => $cm->instance,
                'userid' => $userid, 'latest' => 1]);
            $submitted = $submission && $submission->status === ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            if ($submitted && $gradetext === '') {
                return ['bucket' => 'waiting', 'status' => get_string('ex_waiting', 'local_alphatrade'),
                    'grade' => ''];
            }
            if ($gradetext !== '') {
                return ['bucket' => 'done', 'status' => get_string('ex_graded', 'local_alphatrade'),
                    'grade' => $gradetext];
            }
            return ['bucket' => 'todo', 'status' => get_string('ex_todo', 'local_alphatrade'), 'grade' => ''];
        }

        if ($item['isdone']) {
            return ['bucket' => 'done', 'status' => get_string('ex_passed', 'local_alphatrade'),
                'grade' => $gradetext];
        }
        if ($gradetext !== '') {
            return ['bucket' => 'todo', 'status' => get_string('ex_retry', 'local_alphatrade'),
                'grade' => $gradetext];
        }
        return ['bucket' => 'todo', 'status' => get_string('ex_todo', 'local_alphatrade'), 'grade' => ''];
    }

    /**
     * Results: progression per module and the grades of the programme.
     *
     * @param int $userid
     * @return array
     */
    public static function results(int $userid): array {
        $programme = programme::for_user($userid);
        $course = programme::get_course();
        if (!$programme || !$course) {
            return ['hasmodules' => false, 'modules' => [], 'grades' => [], 'hasgrades' => false];
        }
        $summary = $programme->get_summary();
        $modules = [];
        foreach ($programme->get_modules() as $module) {
            $modules[] = [
                'number' => $module['number'],
                'name' => $module['name'],
                'url' => $module['url'],
                'percent' => $module['percent'],
                'trackeddone' => $module['trackeddone'],
                'trackedcount' => $module['trackedcount'],
                'status' => $module['status'],
                'isdone' => $module['isdone'],
                'islocked' => $module['islocked'],
                'iscurrent' => $module['status'] === 'current',
            ];
        }

        // Graded activities of the programme, in gradebook order.
        $grades = [];
        $total = null;
        $gradeitems = \grade_item::fetch_all(['courseid' => $course->id]);
        if ($gradeitems) {
            \core_collator::asort_objects_by_property($gradeitems, 'sortorder', \core_collator::SORT_NUMERIC);
            foreach ($gradeitems as $gradeitem) {
                $grade = new \grade_grade(['itemid' => $gradeitem->id, 'userid' => $userid], true);
                $value = $grade->finalgrade;
                $row = [
                    'name' => $gradeitem->get_name(),
                    'iscourse' => $gradeitem->itemtype === 'course',
                    'grade' => $value === null ? '' : format_float((float) $value, 1),
                    'max' => format_float((float) $gradeitem->grademax, 1),
                    'percent' => $value !== null && $gradeitem->grademax > 0
                        ? (int) round(100 * $value / $gradeitem->grademax) : null,
                    'hasgrade' => $value !== null,
                ];
                if ($row['iscourse']) {
                    $total = $row;
                } else if ($gradeitem->itemtype === 'mod') {
                    $grades[] = $row;
                }
            }
        }

        return [
            'modules' => $modules,
            'hasmodules' => !empty($modules),
            'grades' => $grades,
            'hasgrades' => !empty($grades),
            'total' => $total,
            'hastotal' => !empty($total),
            'percent' => $summary['percent'],
            'modulesdone' => $summary['modulesdone'],
            'modulestotal' => $summary['modulestotal'],
            'lessonsdone' => $summary['lessonsdone'],
            'lessonstotal' => $summary['lessonstotal'],
            'quizzesdone' => $summary['quizzesdone'],
            'quizzestotal' => $summary['quizzestotal'],
            'week' => $summary['week'],
            'weeks' => $summary['weeks'],
        ];
    }
}
