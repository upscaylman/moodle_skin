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

namespace theme_alphatrade\output;

use context_course;
use context_system;
use moodle_page;
use moodle_url;

/**
 * App navigation as drawn in the maquettes: student sidebar (Portail Etudiant), trainer sidebar
 * (Espace Enseignant) and admin sidebar (Espace Admin), plus the mobile top bar, drawer and tab bar.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation {

    /** @var string[] Student mobile tab bar (maquette: 5 items only). */
    const TABBAR = ['home', 'parcours', 'practice', 'backtest', 'profile'];

    /** @var string[] Student items that go to the mobile drawer. */
    const DRAWER = ['journal', 'tools', 'resources', 'community'];

    /** @var string[] Navigation keys of the trainer space. */
    const TEACHER_KEYS = ['teacher', 'teachercourses', 'reviews', 'studentbacktests', 'gradebook', 'teachercommunity'];

    /** @var string[] Navigation keys of the admin space. */
    const ADMIN_KEYS = ['adminhome', 'adminusers', 'admincourses', 'admincohorts', 'adminreports', 'adminsettings'];

    /**
     * Line icons of the student sidebar, copied from the maquette (24px viewBox, stroke).
     * Paths only; the template wraps them in the svg element.
     */
    const SVG = [
        'home' => '<path d="M3 11l9-8 9 8"></path><path d="M5 10v10h14V10"></path>',
        'parcours' => '<path d="M4 5v14l6-3 6 3 4-2V5l-4 2-6-3-6 3z"></path>',
        'practice' => '<circle cx="12" cy="12" r="9"></circle><path d="M10 8l6 4-6 4z"></path>',
        'backtest' => '<path d="M2 20h20"></path><path d="M6 20V11"></path><path d="M12 20V5"></path><path d="M18 20v-8"></path>',
        'journal' => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 3v18"></path>',
        'tools' => '<circle cx="12" cy="12" r="3"></circle><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"></path>',
        'resources' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>',
        'community' => '<circle cx="8.5" cy="8" r="3"></circle><path d="M2 20c0-3.3 2.9-5.5 6.5-5.5S15 16.7 15 20"></path><circle cx="17" cy="9" r="2.3"></circle><path d="M15.8 14.7c2.6.5 4.2 2.3 4.2 5.3"></path>',
        'profile' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"></path>',
    ];

    /** @var string[] Drawer icons (Phosphor, as in the maquette mobile drawer). */
    const DRAWER_ICONS = [
        'journal' => 'ph-notebook',
        'tools' => 'ph-wrench',
        'resources' => 'ph-books',
        'community' => 'ph-users-three',
    ];

    /** @var moodle_page */
    protected $page;

    /**
     * Constructor.
     *
     * @param moodle_page $page
     */
    public function __construct(moodle_page $page) {
        $this->page = $page;
    }

    /**
     * Template data.
     *
     * @return array
     */
    public function export(): array {
        $active = $this->get_active_key();
        $variant = 'student';
        if (in_array($active, self::TEACHER_KEYS) && $this->can_teach()) {
            $variant = 'teacher';
        } else if ((in_array($active, self::ADMIN_KEYS) || $this->page->pagelayout === 'admin') && $this->is_admin()) {
            $variant = 'admin';
        }

        $data = [
            'isstudent' => $variant === 'student',
            'isteacher' => $variant === 'teacher',
            'isadmin' => $variant === 'admin',
            'spacelabel' => $variant === 'teacher' ? get_string('space_teacher', 'theme_alphatrade')
                : ($variant === 'admin' ? get_string('space_admin', 'theme_alphatrade') : ''),
        ];

        if ($variant === 'student') {
            $items = $this->get_student_items($active);
            $data['main'] = array_values(array_filter($items, function($item) {
                return $item['key'] !== 'profile';
            }));
            $data['secondary'] = array_values(array_filter($items, function($item) {
                return $item['key'] === 'profile';
            }));
            $data['tabbar'] = array_values(array_filter($items, function($item) {
                return in_array($item['key'], self::TABBAR);
            }));
            $data['drawer'] = array_values(array_filter($items, function($item) {
                return in_array($item['key'], self::DRAWER);
            }));
            $switch = [];
            if ($this->can_teach()) {
                $switch[] = $this->item('teacher', 'ph-chalkboard-teacher', new moodle_url('/local/alphatrade/teacher.php'), $active,
                    get_string('space_teacher', 'theme_alphatrade'));
            }
            if ($this->is_admin()) {
                $switch[] = $this->item('adminhome', 'ph-gear-six', new moodle_url('/local/alphatrade/admin.php'), $active,
                    get_string('space_admin', 'theme_alphatrade'));
            }
            $data['switch'] = $switch;
            $data['hasswitch'] = !empty($switch);
        } else {
            $items = $variant === 'teacher' ? $this->get_teacher_items($active) : $this->get_admin_items($active);
            $data['main'] = $items;
            $data['secondary'] = [];
            $data['tabbar'] = $items;
            $data['drawer'] = [];
            $data['switch'] = [$this->item('home', 'ph-student', $this->home_url(), $active,
                get_string('space_student', 'theme_alphatrade'))];
            $data['hasswitch'] = true;
        }
        $data['hasdrawer'] = !empty($data['drawer']);
        return $data;
    }

    /**
     * Student items.
     *
     * @param string $active
     * @return array
     */
    protected function get_student_items(string $active): array {
        $urls = theme_alphatrade_has_app() ? [
            'home' => '/local/alphatrade/index.php',
            'parcours' => '/local/alphatrade/parcours.php',
            'practice' => '/local/alphatrade/practice.php',
            'backtest' => '/local/alphatrade/backtesting.php',
            'journal' => '/local/alphatrade/journal.php',
            'tools' => '/local/alphatrade/tools.php',
            'resources' => '/local/alphatrade/resources.php',
            'community' => '/local/alphatrade/community.php',
            'profile' => '/local/alphatrade/profile.php',
        ] : [
            'home' => '/my/',
            'parcours' => '/my/courses.php',
            'profile' => '/user/profile.php',
        ];
        $items = [];
        foreach ($urls as $key => $path) {
            $item = $this->item($key, self::DRAWER_ICONS[$key] ?? '', new moodle_url($path), $active);
            $item['svg'] = self::SVG[$key];
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Trainer space (maquette Espace Enseignant).
     *
     * @param string $active
     * @return array
     */
    protected function get_teacher_items(string $active): array {
        $programmeid = (int) get_config('local_alphatrade', 'programmecourse');
        return [
            $this->item('teacher', 'ph-house', new moodle_url('/local/alphatrade/teacher.php'), $active),
            $this->item('teachercourses', 'ph-squares-four', $programmeid
                ? new moodle_url('/course/view.php', ['id' => $programmeid]) : new moodle_url('/my/courses.php'), $active),
            $this->item('reviews', 'ph-file-text', new moodle_url('/local/alphatrade/reviews.php'), $active),
            $this->item('studentbacktests', 'ph-chart-bar', new moodle_url('/local/alphatrade/teacher.php', ['view' => 'backtests']),
                $active),
            $this->item('gradebook', 'ph-table', $programmeid
                ? new moodle_url('/grade/report/grader/index.php', ['id' => $programmeid]) : new moodle_url('/grade/index.php'), $active),
            $this->item('teachercommunity', 'ph-users-three', new moodle_url('/local/alphatrade/community.php'), $active),
        ];
    }

    /**
     * Admin space (maquette Espace Admin).
     *
     * @param string $active
     * @return array
     */
    protected function get_admin_items(string $active): array {
        return [
            $this->item('adminhome', 'ph-house', new moodle_url('/local/alphatrade/admin.php'), $active),
            $this->item('adminusers', 'ph-users-three', new moodle_url('/local/alphatrade/admin.php', ['view' => 'users']), $active),
            $this->item('admincourses', 'ph-squares-four', new moodle_url('/local/alphatrade/admin.php', ['view' => 'courses']), $active),
            $this->item('admincohorts', 'ph-stack', new moodle_url('/local/alphatrade/admin.php', ['view' => 'cohorts']), $active),
            $this->item('adminreports', 'ph-chart-bar', new moodle_url('/local/alphatrade/admin.php', ['view' => 'reports']), $active),
            $this->item('adminsettings', 'ph-gear-six', new moodle_url('/admin/search.php'), $active),
        ];
    }

    /**
     * One item.
     *
     * @param string $key
     * @param string $icon
     * @param moodle_url $url
     * @param string $active
     * @param string|null $label
     * @return array
     */
    protected function item(string $key, string $icon, moodle_url $url, string $active, ?string $label = null): array {
        return [
            'key' => $key,
            'label' => $label ?? get_string('nav_' . $key, 'theme_alphatrade'),
            'icon' => $icon,
            'svg' => '',
            'url' => $url->out(false),
            'active' => $key === $active,
        ];
    }

    /**
     * Student home URL.
     *
     * @return moodle_url
     */
    protected function home_url(): moodle_url {
        return theme_alphatrade_has_app() ? new moodle_url('/local/alphatrade/index.php') : new moodle_url('/my/');
    }

    /**
     * Trainer of the programme?
     *
     * @return bool
     */
    protected function can_teach(): bool {
        if (!theme_alphatrade_has_app() || !isloggedin() || isguestuser()) {
            return false;
        }
        $programmeid = (int) get_config('local_alphatrade', 'programmecourse');
        $context = $programmeid ? context_course::instance($programmeid, IGNORE_MISSING) : null;
        return $context && has_capability('local/alphatrade:viewteacher', $context);
    }

    /**
     * Site administrator?
     *
     * @return bool
     */
    protected function is_admin(): bool {
        return isloggedin() && has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Active item: Alpha Trade pages declare it (body class alpha-nav-KEY), native pages are matched.
     *
     * @return string
     */
    protected function get_active_key(): string {
        $classes = ' ' . $this->page->bodyclasses . ' ';
        if (preg_match('/ alpha-nav-([a-z]+) /', $classes, $matches)) {
            return $matches[1];
        }

        $pagetype = $this->page->pagetype;
        $path = $this->page->url ? $this->page->url->get_path() : '';
        if (strpos($pagetype, 'my-index') === 0) {
            return 'home';
        }
        if (strpos($pagetype, 'user-') === 0) {
            return 'profile';
        }
        if (strpos($pagetype, 'grade-report') === 0) {
            return 'gradebook';
        }
        if (strpos($path, '/admin/user.php') !== false || strpos($pagetype, 'admin-user') === 0) {
            return 'adminusers';
        }
        if (strpos($pagetype, 'course-management') === 0) {
            return 'admincourses';
        }
        if (strpos($pagetype, 'cohort-') === 0) {
            return 'admincohorts';
        }
        if (strpos($pagetype, 'admin-') === 0) {
            return 'adminsettings';
        }

        if (theme_alphatrade_has_app() && $this->page->course->id != SITEID) {
            $config = get_config('local_alphatrade');
            $courseid = (int) $this->page->course->id;
            if ($courseid == ($config->programmecourse ?? 0) && strpos($pagetype, 'mod-forum-') === 0) {
                return 'community';
            }
            if ($courseid == ($config->practicecourse ?? 0)) {
                return 'practice';
            }
            if ($courseid == ($config->resourcescourse ?? 0)) {
                return 'resources';
            }
            return 'parcours';
        }
        return '';
    }
}
