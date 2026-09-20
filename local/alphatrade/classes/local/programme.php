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

use cm_info;
use completion_info;
use context_course;
use moodle_url;
use section_info;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * The programme as the student sees it. Moodle mapping:
 * one course = the programme, each (listed) section = one module, each activity = one lesson,
 * quizzes = module evaluations, restrictions = locked states, completion = progress.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class programme {

    /** @var string[] Phosphor icon per activity type. */
    const ICONS = [
        'assign' => 'ph-clipboard-text',
        'book' => 'ph-book-open',
        'choice' => 'ph-list-checks',
        'customcert' => 'ph-certificate',
        'data' => 'ph-database',
        'feedback' => 'ph-chat-centered-text',
        'folder' => 'ph-folder',
        'forum' => 'ph-chats-circle',
        'glossary' => 'ph-list-magnifying-glass',
        'h5pactivity' => 'ph-cursor-click',
        'lesson' => 'ph-book-open-text',
        'lti' => 'ph-plugs',
        'page' => 'ph-article',
        'quiz' => 'ph-exam',
        'resource' => 'ph-file-text',
        'scorm' => 'ph-monitor-play',
        'url' => 'ph-play-circle',
        'wiki' => 'ph-notebook',
        'workshop' => 'ph-users-four',
    ];

    /** @var programme[] Per-request cache, keyed by user id. */
    protected static $instances = [];

    /** @var stdClass */
    protected $course;

    /** @var int */
    protected $userid;

    /** @var \course_modinfo */
    protected $modinfo;

    /** @var completion_info */
    protected $completion;

    /** @var array|null */
    protected $modules = null;

    /**
     * Titre d'affichage d'un module : « Module 02 - <nom> », sans repeter le numero
     * quand le nom de section le porte deja (nos sections s'appellent « Module 1 - ... »).
     * Le nom fait foi : la numerotation pedagogique (Module 0) ne suit pas la position (01).
     *
     * @param array $module as returned by get_modules()
     * @return string
     */
    public static function module_title(array $module): string {
        $parts = self::module_parts($module);
        return $parts['label'] . ' - ' . $parts['title'];
    }

    /**
     * Le module coupe en deux : son numero affichable et son titre. Nos sections s'appellent
     * « Module 1 - Fondamentaux » ; le numero pedagogique porte par le nom fait foi, il ne suit
     * pas la position (le Module 0 est la premiere section). Sans numero dans le nom, la position sert.
     *
     * @param array $module as returned by get_modules()
     * @return array{label: string, title: string}
     */
    public static function module_parts(array $module): array {
        return [
            'label' => $module['label'] ?? get_string('modulelabel', 'local_alphatrade', $module['number']),
            'title' => (string) $module['name'],
        ];
    }

    /**
     * The configured programme course, if any.
     *
     * @return stdClass|null
     */
    public static function get_course(): ?stdClass {
        global $DB;
        $courseid = (int) get_config('local_alphatrade', 'programmecourse');
        if (!$courseid || $courseid == SITEID) {
            return null;
        }
        return $DB->get_record('course', ['id' => $courseid]) ?: null;
    }

    /**
     * Programme for a user (cached for the request).
     *
     * @param int $userid
     * @return programme|null
     */
    public static function for_user(int $userid): ?programme {
        if (!array_key_exists($userid, self::$instances)) {
            $course = self::get_course();
            self::$instances[$userid] = $course ? new programme($course, $userid) : null;
        }
        return self::$instances[$userid];
    }

    /**
     * Constructor.
     *
     * @param stdClass $course
     * @param int $userid
     */
    public function __construct(stdClass $course, int $userid) {
        $this->course = $course;
        $this->userid = $userid;
        $this->modinfo = get_fast_modinfo($course, $userid);
        $this->completion = new completion_info($course);
    }

    /**
     * @return stdClass
     */
    public function get_course_record(): stdClass {
        return $this->course;
    }

    /**
     * @return context_course
     */
    public function get_context(): context_course {
        return context_course::instance($this->course->id);
    }

    /**
     * All modules with their lessons, progress and state.
     *
     * @return array
     */
    public function get_modules(): array {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $context = $this->get_context();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context, $this->userid);
        $modules = [];
        $number = 0;
        foreach ($this->modinfo->get_listed_section_info_all() as $section) {
            if ($section->section == 0 || (!$section->visible && !$canviewhidden)) {
                continue;
            }
            $number++;
            $modules[] = $this->build_module($section, $number);
        }

        // Current module: first one that is neither locked nor done.
        $currentfound = false;
        $months = $this->get_month_boundaries(count($modules));
        foreach ($modules as $index => &$module) {
            if ($module['status'] === 'todo' && !$currentfound) {
                $module['status'] = 'current';
                $currentfound = true;
            }
            $module['month'] = $months[$index];
            $module = array_merge($module, self::status_flags($module['status']));
            $this->flag_current_item($module);
        }
        unset($module);

        $this->modules = $modules;
        return $modules;
    }

    /**
     * One module by section number.
     *
     * @param int $sectionnum
     * @return array|null
     */
    public function get_module(int $sectionnum): ?array {
        foreach ($this->get_modules() as $module) {
            if ($module['sectionnum'] == $sectionnum) {
                return $module;
            }
        }
        return null;
    }

    /**
     * The module the student is working on (null once everything is done or locked).
     *
     * @return array|null
     */
    public function get_current_module(): ?array {
        foreach ($this->get_modules() as $module) {
            if ($module['status'] === 'current') {
                return $module;
            }
        }
        return null;
    }

    /**
     * The single next thing to do: first unfinished, unlocked item of the current module.
     *
     * @return array|null [module, item]
     */
    public function get_next(): ?array {
        $module = $this->get_current_module();
        if (!$module) {
            return null;
        }
        foreach ($module['items'] as $item) {
            if ($item['status'] === 'current') {
                return ['module' => $module, 'item' => $item];
            }
        }
        return ['module' => $module, 'item' => null];
    }

    /**
     * Global figures for the dashboard and the profile.
     *
     * @return array
     */
    public function get_summary(): array {
        $summary = [
            'modulestotal' => 0, 'modulesdone' => 0,
            'lessonstotal' => 0, 'lessonsdone' => 0,
            'quizzestotal' => 0, 'quizzesdone' => 0,
            'trackedtotal' => 0, 'trackeddone' => 0,
        ];
        foreach ($this->get_modules() as $module) {
            $summary['modulestotal']++;
            $summary['modulesdone'] += $module['isdone'] ? 1 : 0;
            $summary['lessonstotal'] += $module['lessoncount'];
            $summary['lessonsdone'] += $module['lessonsdone'];
            $summary['quizzestotal'] += $module['quizcount'];
            $summary['quizzesdone'] += $module['quizzesdone'];
            $summary['trackedtotal'] += $module['trackedcount'];
            $summary['trackeddone'] += $module['trackeddone'];
        }
        $summary['percent'] = $summary['trackedtotal'] ? (int) floor(100 * $summary['trackeddone'] / $summary['trackedtotal']) : 0;
        $summary['weeks'] = max(1, (int) get_config('local_alphatrade', 'programmeweeks'));
        $summary['week'] = $this->get_week($summary['weeks']);
        $summary['iscomplete'] = $summary['modulestotal'] > 0 && $summary['modulesdone'] == $summary['modulestotal'];
        return $summary;
    }

    /**
     * Current programme week, counted from the student's enrolment start.
     *
     * @param int $weeks
     * @return int
     */
    public function get_week(int $weeks): int {
        global $DB;
        $sql = "SELECT MIN(CASE WHEN ue.timestart > 0 THEN ue.timestart ELSE ue.timecreated END)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid AND e.courseid = :courseid";
        $start = (int) $DB->get_field_sql($sql, ['userid' => $this->userid, 'courseid' => $this->course->id]);
        if (!$start) {
            $start = (int) $this->course->startdate;
        }
        if (!$start || $start > time()) {
            return 1;
        }
        return min($weeks, (int) floor((time() - $start) / WEEKSECS) + 1);
    }

    /**
     * Modules grouped by month ("Mois 1 - Comprendre"...).
     *
     * @return array
     */
    public function get_months(): array {
        $months = [];
        foreach ($this->get_modules() as $module) {
            $index = $module['month'];
            if (!isset($months[$index])) {
                $key = 'month' . $index . 'theme';
                $theme = get_string_manager()->string_exists($key, 'local_alphatrade')
                    ? get_string($key, 'local_alphatrade') : '';
                $months[$index] = [
                    'number' => $index,
                    'label' => get_string('monthlabel', 'local_alphatrade', $index),
                    'theme' => $theme,
                    'modules' => [],
                ];
            }
            $months[$index]['modules'][] = $module;
        }
        return array_values($months);
    }

    /**
     * Section used by the final project ("Alpha Trading System"): setting, or the last module.
     *
     * @return array|null
     */
    public function get_project_module(): ?array {
        $sectionnum = (int) get_config('local_alphatrade', 'projectsection');
        if ($sectionnum) {
            return $this->get_module($sectionnum);
        }
        $modules = $this->get_modules();
        return $modules ? end($modules) : null;
    }

    /**
     * Header/footer data for the lesson reader and the quiz, used by theme_alphatrade.
     *
     * @param cm_info $cm
     * @param int $userid
     * @return array|null
     */
    public static function lesson_context(cm_info $cm, int $userid): ?array {
        $programme = self::for_user($userid);
        if (!$programme || $cm->course != $programme->course->id) {
            return null;
        }

        $modules = $programme->get_modules();
        foreach ($modules as $moduleindex => $module) {
            foreach ($module['items'] as $itemindex => $item) {
                if ($item['cmid'] != $cm->id) {
                    continue;
                }
                return $programme->build_lesson_context($cm, $modules, $moduleindex, $itemindex);
            }
        }
        return null;
    }

    /**
     * Build the lesson context once the cm has been located.
     *
     * @param cm_info $cm
     * @param array $modules
     * @param int $moduleindex
     * @param int $itemindex
     * @return array
     */
    protected function build_lesson_context(cm_info $cm, array $modules, int $moduleindex, int $itemindex): array {
        $module = $modules[$moduleindex];
        $item = $module['items'][$itemindex];

        // Next unlocked item: same module first, then the next modules.
        $next = null;
        $nextlabel = get_string('nextlesson', 'local_alphatrade');
        for ($i = $itemindex + 1; $i < count($module['items']); $i++) {
            if (!$module['items'][$i]['islocked']) {
                $next = $module['items'][$i];
                break;
            }
        }
        if ($next && $next['isquiz']) {
            $nextlabel = get_string('startevaluation', 'local_alphatrade');
        }
        if (!$next) {
            for ($m = $moduleindex + 1; $m < count($modules) && !$next; $m++) {
                foreach ($modules[$m]['items'] as $candidate) {
                    if (!$candidate['islocked']) {
                        $next = $candidate;
                        $nextlabel = get_string('nextmodule', 'local_alphatrade');
                        break;
                    }
                }
            }
        }

        $tracking = $this->completion->is_enabled($cm);
        $complete = (bool) $item['isdone'];
        $lessonindex = 0;
        foreach ($module['lessons'] as $position => $lesson) {
            if ($lesson['cmid'] == $cm->id) {
                $lessonindex = $position + 1;
            }
        }

        return [
            'isquiz' => $item['isquiz'],
            'title' => $item['name'],
            'moduleurl' => $module['url'],
            'modulename' => $module['name'],
            'modulenumber' => $module['number'],
            'modulelabel' => get_string('modulelabel', 'local_alphatrade', $module['number']),
            // Rail de la lecon : la liste plate des items du module, comme dans la maquette v4.
            'raillabel' => self::module_title($module),
            'rail' => array_map(function($item, $index) use ($cm) {
                return [
                    'number' => sprintf('%02d', $index + 1),
                    'title' => $item['name'],
                    'url' => $item['url'],
                    'iconclass' => self::maquette_icon($item['status']),
                    'statusclass' => self::maquette_status_class($item['status']),
                    'iscurrent' => $item['cmid'] == $cm->id,
                    'islocked' => $item['status'] === 'locked',
                ];
            }, $module['items'], array_keys($module['items'])),
            'counter' => get_string('lessoncounter', 'local_alphatrade', [
                'index' => sprintf('%02d', max(1, $lessonindex)),
                'count' => sprintf('%02d', $module['lessoncount']),
            ]),
            'hasnext' => !empty($next),
            'nexturl' => $next ? $next['url'] : (new moodle_url('/local/alphatrade/parcours.php'))->out(false),
            'nextlabel' => $next ? $nextlabel : get_string('backtoparcours', 'local_alphatrade'),
            'hasmanualcompletion' => $tracking == COMPLETION_TRACKING_MANUAL,
            'hasautocompletion' => $tracking == COMPLETION_TRACKING_AUTOMATIC,
            'iscomplete' => $complete,
            'completiontoggle' => [
                'cmid' => $cm->id,
                'activityname' => $item['name'],
                'overallcomplete' => $complete,
                'overallincomplete' => !$complete,
                'istrackeduser' => $this->completion->is_tracked_user($this->userid),
                'withavailability' => 0,
            ],
        ];
    }

    /**
     * Build one module from a course section.
     *
     * @param section_info $section
     * @param int $number
     * @return array
     */
    protected function build_module(section_info $section, int $number): array {
        $items = [];
        foreach ($this->get_section_cms($section) as $cm) {
            $item = $this->build_item($cm);
            if ($item) {
                $items[] = $item;
            }
        }

        $lessons = array_values(array_filter($items, function($item) {
            return !$item['isquiz'];
        }));
        $quizzes = array_values(array_filter($items, function($item) {
            return $item['isquiz'];
        }));
        foreach ($lessons as $position => &$lesson) {
            $lesson['number'] = sprintf('%02d', $position + 1);
        }
        unset($lesson);

        $tracked = array_filter($items, function($item) {
            return $item['tracked'];
        });
        $trackeddone = count(array_filter($tracked, function($item) {
            return $item['isdone'];
        }));
        $lessonsdone = count(array_filter($lessons, function($item) {
            return $item['isdone'];
        }));
        $quizzesdone = count(array_filter($quizzes, function($item) {
            return $item['isdone'];
        }));

        $locked = !$section->available || !$section->uservisible;
        if ($locked) {
            $status = 'locked';
        } else if (count($tracked) > 0 && $trackeddone == count($tracked)) {
            $status = 'done';
        } else {
            $status = 'todo';
        }

        $name = get_section_name($this->course, $section);
        $summary = format_text($section->summary, $section->summaryformat, [
            'context' => $this->get_context(),
            'noclean' => false,
            'overflowdiv' => false,
        ]);
        list($description, $objectives) = self::split_summary($summary);

        $availableinfo = '';
        if ($locked && !empty($section->availableinfo)) {
            $availableinfo = \core_availability\info::format_info($section->availableinfo, $this->course);
        }

        // Nos sections s'appellent « Module 1 - Fondamentaux » : le numero pedagogique du nom
        // fait foi (le Module 0 est la premiere section), sinon la position sert de numero.
        $label = '';
        if (preg_match('/^(module\s*(\d+))\s*[-\x{2013}\x{2014}:.]?\s*(.+)$/iu', trim($name), $matches)) {
            $label = trim($matches[1]);
            $number = $matches[2];
            $name = trim($matches[3]);
        }

        return [
            'id' => $section->id,
            'sectionnum' => $section->section,
            // Toujours sur deux chiffres : les pastilles de l'accueil et du parcours s'alignent.
            'number' => sprintf('%02d', (int) $number),
            'label' => $label === '' ? get_string('modulelabel', 'local_alphatrade', sprintf('%02d', $number)) : $label,
            'name' => $name,
            'description' => $description,
            'objectives' => $objectives,
            'hasobjectives' => !empty($objectives),
            'url' => (new moodle_url('/local/alphatrade/module.php', ['section' => $section->section]))->out(false),
            'items' => $items,
            'lessons' => $lessons,
            'quizzes' => $quizzes,
            'hasquiz' => !empty($quizzes),
            'lessoncount' => count($lessons),
            'lessonsdone' => $lessonsdone,
            'quizcount' => count($quizzes),
            'quizzesdone' => $quizzesdone,
            'trackedcount' => count($tracked),
            'trackeddone' => $trackeddone,
            'percent' => count($tracked) ? (int) floor(100 * $trackeddone / count($tracked)) : 0,
            'status' => $status,
            'availableinfo' => $availableinfo,
            'month' => 1,
        ];
    }

    /**
     * Activities of a section, with subsections (Moodle 4.5) flattened in place.
     *
     * @param section_info $section
     * @return cm_info[]
     */
    protected function get_section_cms(section_info $section): array {
        $cms = [];
        foreach ($this->modinfo->sections[$section->section] ?? [] as $cmid) {
            $cm = $this->modinfo->get_cm($cmid);
            if ($cm->modname === 'subsection') {
                $delegated = $cm->get_delegated_section_info();
                if ($delegated && $delegated->uservisible) {
                    foreach ($this->modinfo->sections[$delegated->section] ?? [] as $subcmid) {
                        $cms[] = $this->modinfo->get_cm($subcmid);
                    }
                }
                continue;
            }
            $cms[] = $cm;
        }
        return $cms;
    }

    /**
     * One lesson / evaluation item.
     *
     * @param cm_info $cm
     * @return array|null
     */
    protected function build_item(cm_info $cm): ?array {
        if (!empty($cm->deletioninprogress) || !$cm->url || $cm->modname === 'label') {
            return null;
        }
        if (!$cm->uservisible && empty($cm->availableinfo)) {
            return null;
        }

        $tracked = $this->completion->is_enabled($cm) != COMPLETION_TRACKING_NONE;
        $isdone = false;
        if ($tracked) {
            $state = $this->completion->get_data($cm, false, $this->userid)->completionstate;
            $isdone = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]);
        }
        $locked = !$cm->uservisible;

        $availableinfo = '';
        if ($locked && !empty($cm->availableinfo)) {
            $availableinfo = \core_availability\info::format_info($cm->availableinfo, $this->course);
        }

        return [
            'cmid' => $cm->id,
            'name' => $cm->get_formatted_name(),
            'url' => $locked ? '' : $cm->url->out(false),
            'modname' => $cm->modname,
            'icon' => self::ICONS[$cm->modname] ?? 'ph-circle',
            'isquiz' => $cm->modname === 'quiz',
            'tracked' => $tracked,
            'isdone' => $isdone,
            'islocked' => $locked,
            'status' => $locked ? 'locked' : ($isdone ? 'done' : 'todo'),
            'availableinfo' => $availableinfo,
            'number' => '',
        ];
    }

    /**
     * Mark the first unfinished item of a current/todo module as "current".
     *
     * @param array $module
     */
    protected function flag_current_item(array &$module): void {
        $found = false;
        foreach ($module['items'] as &$item) {
            if (!$found && $module['status'] !== 'locked' && $item['status'] === 'todo') {
                $item['status'] = 'current';
                $found = true;
            }
            $item = array_merge($item, self::status_flags($item['status']));
        }
        unset($item);
        // Keep lessons/quizzes in sync with items.
        $bycmid = [];
        foreach ($module['items'] as $item) {
            $bycmid[$item['cmid']] = $item;
        }
        foreach (['lessons', 'quizzes'] as $list) {
            foreach ($module[$list] as &$entry) {
                $number = $entry['number'];
                $entry = $bycmid[$entry['cmid']];
                $entry['number'] = $number;
            }
            unset($entry);
        }
    }

    /**
     * Template flags and icon for a status.
     *
     * @param string $status done|current|todo|locked
     * @return array
     */
    public static function status_flags(string $status): array {
        $icons = [
            'done' => 'ph-fill ph-check-circle',
            'current' => 'ph-fill ph-play-circle',
            'todo' => 'ph ph-circle',
            'locked' => 'ph ph-lock-simple',
        ];
        return [
            'isdone' => $status === 'done',
            'iscurrent' => $status === 'current',
            'istodo' => $status === 'todo',
            'islocked' => $status === 'locked',
            'statusicon' => $icons[$status],
            'statuslabel' => get_string('status_' . $status, 'local_alphatrade'),
        ];
    }

    /**
     * Status icon of the maquette (check for done, filled dot for current, lock for locked).
     *
     * @param string $status
     * @return string
     */
    public static function maquette_icon(string $status): string {
        $icons = [
            'done' => 'ph-fill ph-check-circle',
            'current' => 'ph-fill ph-circle',
            'todo' => 'ph ph-circle',
            'locked' => 'ph ph-lock-simple',
        ];
        return $icons[$status] ?? 'ph ph-circle';
    }

    /**
     * Colour class of a status icon.
     *
     * @param string $status
     * @return string
     */
    public static function maquette_status_class(string $status): string {
        return 'at-status-' . $status;
    }

    /**
     * Month index (1-based) of each module, from the "3,4,5" setting.
     *
     * @param int $count
     * @return int[]
     */
    protected function get_month_boundaries(int $count): array {
        $split = array_filter(array_map('intval', explode(',', (string) get_config('local_alphatrade', 'monthsplit'))));
        if (!$split) {
            $size = max(1, (int) ceil($count / 3));
            $split = [$size, $size, $size];
        }
        $months = [];
        $month = 1;
        $left = $split[0];
        for ($i = 0; $i < $count; $i++) {
            while ($left <= 0 && $month < count($split)) {
                $month++;
                $left = $split[$month - 1];
            }
            $months[$i] = $month;
            $left--;
        }
        return $months;
    }

    /**
     * Section summary convention: paragraphs = description, list items = objectives.
     *
     * @param string $html
     * @return array [string description html, array objectives]
     */
    public static function split_summary(string $html): array {
        if (trim(strip_tags($html)) === '') {
            return ['', []];
        }
        $objectives = [];
        if (preg_match_all('~<li[^>]*>(.*?)</li>~is', $html, $matches)) {
            foreach ($matches[1] as $li) {
                $text = trim(html_entity_decode(strip_tags($li), ENT_QUOTES, 'UTF-8'));
                if ($text !== '') {
                    $objectives[] = ['text' => $text];
                }
            }
        }
        $description = trim(preg_replace('~<(ul|ol)[^>]*>.*?</\1>~is', '', $html));
        return [$description, $objectives];
    }
}
