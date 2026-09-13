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
 * Alpha Trade app navigation: desktop sidebar, mobile tab bar and mobile menu share one model.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation {

    /** @var string[] Items shown in the mobile tab bar (5 max, the rest goes to the menu). */
    const TABBAR = ['home', 'parcours', 'practice', 'backtest', 'profile'];

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
     * Template data for the sidebar, tab bar and mobile menu.
     *
     * @return array
     */
    public function export(): array {
        $active = $this->get_active_key();
        $main = [];
        $secondary = [];
        foreach ($this->get_student_items() as $item) {
            $item['active'] = ($item['key'] === $active);
            $item['intabbar'] = in_array($item['key'], self::TABBAR);
            if ($item['key'] === 'profile') {
                $secondary[] = $item;
            } else {
                $main[] = $item;
            }
        }
        $tabbar = array_values(array_filter(array_merge($main, $secondary), function($item) {
            return $item['intabbar'];
        }));
        $staff = $this->get_staff_items($active);

        return [
            'main' => $main,
            'secondary' => $secondary,
            'tabbar' => $tabbar,
            'staff' => $staff,
            'hasstaff' => !empty($staff),
        ];
    }

    /**
     * Student navigation (wireframe v3). Falls back to native Moodle pages without local_alphatrade.
     *
     * @return array
     */
    protected function get_student_items(): array {
        if (!theme_alphatrade_has_app()) {
            return [
                $this->item('home', 'ph-house', new moodle_url('/my/')),
                $this->item('parcours', 'ph-path', new moodle_url('/my/courses.php')),
                $this->item('profile', 'ph-user-circle', new moodle_url('/user/profile.php')),
            ];
        }
        return [
            $this->item('home', 'ph-house', new moodle_url('/local/alphatrade/index.php')),
            $this->item('parcours', 'ph-path', new moodle_url('/local/alphatrade/parcours.php')),
            $this->item('practice', 'ph-flask', new moodle_url('/local/alphatrade/practice.php')),
            $this->item('backtest', 'ph-chart-line-up', new moodle_url('/local/alphatrade/backtesting.php')),
            $this->item('journal', 'ph-notebook', new moodle_url('/local/alphatrade/journal.php')),
            $this->item('tools', 'ph-wrench', new moodle_url('/local/alphatrade/tools.php')),
            $this->item('resources', 'ph-books', new moodle_url('/local/alphatrade/resources.php')),
            $this->item('community', 'ph-users-three', new moodle_url('/local/alphatrade/community.php')),
            $this->item('profile', 'ph-user-circle', new moodle_url('/local/alphatrade/profile.php')),
        ];
    }

    /**
     * Teacher / admin shortcuts. Only shown to users who can manage the programme or the site.
     *
     * @param string $active
     * @return array
     */
    protected function get_staff_items(string $active): array {
        $items = [];
        $programmeid = theme_alphatrade_has_app() ? (int) get_config('local_alphatrade', 'programmecourse') : 0;
        $programmecontext = $programmeid ? context_course::instance($programmeid, IGNORE_MISSING) : null;

        if ($programmecontext && has_capability('local/alphatrade:viewteacher', $programmecontext)) {
            $items[] = $this->item('teacher', 'ph-chalkboard-teacher', new moodle_url('/local/alphatrade/teacher.php'));
            $items[] = $this->item('reviews', 'ph-check-square-offset', new moodle_url('/local/alphatrade/reviews.php'));
            $items[] = $this->item('studentbacktests', 'ph-chart-bar',
                new moodle_url('/local/alphatrade/teacher.php', ['view' => 'backtests']));
            $items[] = $this->item('programmecourse', 'ph-graduation-cap',
                new moodle_url('/course/view.php', ['id' => $programmeid]));
            $items[] = $this->item('grades', 'ph-exam', new moodle_url('/grade/report/grader/index.php', ['id' => $programmeid]));
        }
        if (has_capability('moodle/site:config', context_system::instance())) {
            $items[] = $this->item('siteadmin', 'ph-sliders-horizontal', new moodle_url('/admin/search.php'));
        }
        foreach ($items as &$item) {
            $item['active'] = ($item['key'] === $active);
        }
        return $items;
    }

    /**
     * Build one navigation item.
     *
     * @param string $key
     * @param string $icon Phosphor icon class
     * @param moodle_url $url
     * @return array
     */
    protected function item(string $key, string $icon, moodle_url $url): array {
        return [
            'key' => $key,
            'label' => get_string('nav_' . $key, 'theme_alphatrade'),
            'icon' => $icon,
            'url' => $url->out(false),
        ];
    }

    /**
     * Which item is active. Alpha Trade pages declare it with a body class (alpha-nav-KEY),
     * native Moodle pages are matched on their course or page type.
     *
     * @return string
     */
    protected function get_active_key(): string {
        $classes = ' ' . $this->page->bodyclasses . ' ';
        if (preg_match('/ alpha-nav-([a-z]+) /', $classes, $matches)) {
            return $matches[1];
        }

        $pagetype = $this->page->pagetype;
        if (strpos($pagetype, 'my-index') === 0) {
            return 'home';
        }
        if (strpos($pagetype, 'user-') === 0) {
            return 'profile';
        }
        if (strpos($pagetype, 'admin-') === 0) {
            return 'siteadmin';
        }

        if (theme_alphatrade_has_app() && !empty($this->page->course->id) && $this->page->course->id != SITEID) {
            $config = get_config('local_alphatrade');
            $courseid = (int) $this->page->course->id;
            if (strpos($pagetype, 'mod-forum-') === 0 && $courseid == ($config->programmecourse ?? 0)) {
                return 'community';
            }
            $map = [
                'programmecourse' => 'parcours',
                'practicecourse' => 'practice',
                'resourcescourse' => 'resources',
            ];
            foreach ($map as $setting => $key) {
                if (!empty($config->$setting) && $courseid == $config->$setting) {
                    return $key;
                }
            }
            return 'parcours';
        }
        return '';
    }
}
