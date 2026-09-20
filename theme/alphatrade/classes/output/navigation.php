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
 * The four navigation trees of the Alpha Trade architecture, one per rôle métier
 * (Alpha-Trade-Architecture-Globale.md § 2, ARCHITECTURE-ALPHA-TRADE.md § 7):
 *
 *   Élève      Accueil · Ma formation · Vidéos · Live · Communauté · Exercices · Mes résultats · Mon profil
 *   Enseignant Accueil · Mes formations · Vidéos · Live · Communauté · Mes élèves · Créer · À corriger ·
 *              Résultats · Mon profil
 *   Partenaire Accueil · Mes élèves · Mes formations · Vidéos · Live · Communauté · Progression · Mon profil
 *   Admin      Dashboard · Formations · Vidéos · Lives · Communauté · Catégories · Utilisateurs · Contenus ·
 *              Évaluations · Certifications · Statistiques · Journal · Paramètres
 *
 * Vidéos and Communauté stay in the main list of every rôle, never in a submenu (règle UX absolue).
 * The Alpha Trade practice tools (Practice, Backtest, Journal, Outils, Ressources) follow in a second
 * group of the student sidebar: they are programme tools, not one of the six piliers.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation {

    /** @var string[] Student mobile tab bar (maquette: 5 items only). */
    const TABBAR = ['home', 'parcours', 'videos', 'exercices', 'profile'];

    /** @var string[] Student items that go to the mobile drawer. */
    const DRAWER = ['live', 'community', 'resultats', 'practice', 'backtest', 'journal', 'tools', 'resources',
        'referral'];

    /** @var string[] Student practice tools, shown under the six piliers. */
    const STUDENT_TOOLS = ['practice', 'backtest', 'journal', 'tools', 'resources', 'referral'];

    /** @var string[] Navigation keys of the trainer space. */
    const TEACHER_KEYS = ['teacher', 'teachercourses', 'teacherstudents', 'create', 'reviews', 'studentbacktests',
        'gradebook', 'teachercommunity'];

    /** @var string[] Navigation keys of the partner space. */
    const PARTNER_KEYS = ['partner', 'partnerstudents', 'partnercourses', 'partnerprogress'];

    /**
     * @var string[] Keys shared by several spaces: Vidéos, Live, Communauté and Mon profil exist
     * in the four trees. They never change the space by themselves; the last space the user was
     * in wins, so a trainer who opens Vidéos stays in the trainer space.
     */
    const SHARED_KEYS = ['videos', 'live', 'community', 'profile'];

    /** @var string[] Navigation keys of the admin space. */
    const ADMIN_KEYS = ['adminhome', 'admincourses', 'admincategories', 'adminusers', 'admincontents',
        'adminassessments', 'admincertifications', 'adminstats', 'adminlog', 'adminsettings'];

    /**
     * Line icons of the student sidebar, copied from the maquette (24px viewBox, stroke).
     * Paths only; the template wraps them in the svg element.
     */
    const SVG = [
        'home' => '<path d="M3 11l9-8 9 8"></path><path d="M5 10v10h14V10"></path>',
        'parcours' => '<path d="M4 5v14l6-3 6 3 4-2V5l-4 2-6-3-6 3z"></path>',
        'videos' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M10 9.2l5 2.8-5 2.8z"></path>',
        'live' => '<circle cx="12" cy="12" r="2.6"></circle><path d="M7.4 7.4a6.5 6.5 0 0 0 0 9.2"></path><path d="M16.6 7.4a6.5 6.5 0 0 1 0 9.2"></path><path d="M4.6 4.6a10.5 10.5 0 0 0 0 14.8"></path><path d="M19.4 4.6a10.5 10.5 0 0 1 0 14.8"></path>',
        'exercices' => '<path d="M10 6h10M10 12h10M10 18h10"></path><path d="M3.5 6l1.3 1.3L7.3 4.8"></path><path d="M3.5 12l1.3 1.3L7.3 10.8"></path><path d="M3.5 18l1.3 1.3L7.3 16.8"></path>',
        'resultats' => '<path d="M4 4v16h16"></path><path d="M8 16V10M13 16V6M18 16v-4"></path>',
        'practice' => '<circle cx="12" cy="12" r="9"></circle><path d="M10 8l6 4-6 4z"></path>',
        'backtest' => '<path d="M2 20h20"></path><path d="M6 20V11"></path><path d="M12 20V5"></path><path d="M18 20v-8"></path>',
        'journal' => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 3v18"></path>',
        'tools' => '<circle cx="12" cy="12" r="3"></circle><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"></path>',
        'resources' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>',
        'referral' => '<circle cx="9" cy="8" r="3.2"></circle><path d="M3 20c0-3.3 2.7-5.5 6-5.5"></path><path d="M16 13.5l2.2 2.2L22 12"></path><path d="M14.5 8.5h5"></path>',
        'community' => '<circle cx="8.5" cy="8" r="3"></circle><path d="M2 20c0-3.3 2.9-5.5 6.5-5.5S15 16.7 15 20"></path><circle cx="17" cy="9" r="2.3"></circle><path d="M15.8 14.7c2.6.5 4.2 2.3 4.2 5.3"></path>',
        'profile' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"></path>',
    ];

    /** @var string[] Phosphor icons, used by the mobile drawer and the staff sidebars. */
    const ICONS = [
        'home' => 'ph-house',
        'parcours' => 'ph-graduation-cap',
        'videos' => 'ph-play-circle',
        'live' => 'ph-broadcast',
        'community' => 'ph-users-three',
        'exercices' => 'ph-clipboard-text',
        'resultats' => 'ph-chart-bar',
        'practice' => 'ph-flask',
        'backtest' => 'ph-chart-line',
        'journal' => 'ph-notebook',
        'tools' => 'ph-wrench',
        'resources' => 'ph-books',
        'referral' => 'ph-handshake',
        'profile' => 'ph-user',
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
        } else if (in_array($active, self::PARTNER_KEYS) && $this->can_follow()) {
            $variant = 'partner';
        } else if ((in_array($active, self::ADMIN_KEYS) || $this->page->pagelayout === 'admin') && $this->is_admin()) {
            $variant = 'admin';
        } else if (in_array($active, self::SHARED_KEYS)) {
            $variant = $this->remembered_space();
        }
        if (!in_array($active, self::SHARED_KEYS)) {
            $this->remember_space($variant);
        }

        $labels = [
            'teacher' => 'space_teacher',
            'partner' => 'space_partner',
            'admin' => 'space_admin',
        ];
        $data = [
            'isstudent' => $variant === 'student',
            'isteacher' => $variant === 'teacher',
            'ispartner' => $variant === 'partner',
            'isadmin' => $variant === 'admin',
            'spacelabel' => isset($labels[$variant]) ? get_string($labels[$variant], 'theme_alphatrade') : '',
        ];

        if ($variant === 'student') {
            $items = $this->get_student_items($active);
            $data['main'] = array_values(array_filter($items, function($item) {
                return $item['key'] !== 'profile' && !in_array($item['key'], self::STUDENT_TOOLS);
            }));
            $data['tools'] = array_values(array_filter($items, function($item) {
                return in_array($item['key'], self::STUDENT_TOOLS);
            }));
            $data['hastools'] = !empty($data['tools']);
            $data['toolslabel'] = get_string('nav_toolsgroup', 'theme_alphatrade');
            $data['secondary'] = array_values(array_filter($items, function($item) {
                return $item['key'] === 'profile';
            }));
            $data['tabbar'] = array_values(array_filter($items, function($item) {
                return in_array($item['key'], self::TABBAR);
            }));
            $data['drawer'] = array_values(array_filter($items, function($item) {
                return in_array($item['key'], self::DRAWER);
            }));
            $data['switch'] = $this->get_switch($active);
            $data['hasswitch'] = !empty($data['switch']);
        } else {
            $builders = [
                'teacher' => 'get_teacher_items',
                'partner' => 'get_partner_items',
                'admin' => 'get_admin_items',
            ];
            $items = $this->{$builders[$variant]}($active);
            $data['main'] = $items;
            $data['tools'] = [];
            $data['hastools'] = false;
            $data['secondary'] = [];
            $data['tabbar'] = array_slice($items, 0, 5);
            $data['drawer'] = array_slice($items, 5);
            // The other spaces this user holds, the current one excluded.
            $keys = ['teacher' => self::TEACHER_KEYS, 'partner' => self::PARTNER_KEYS, 'admin' => self::ADMIN_KEYS];
            $data['switch'] = [$this->item('home', 'ph-student', $this->home_url(), $active,
                get_string('space_student', 'theme_alphatrade'))];
            foreach ($this->get_switch($active) as $tag) {
                if (!in_array($tag['key'], $keys[$variant])) {
                    $data['switch'][] = $tag;
                }
            }
            $data['hasswitch'] = true;
        }
        $data['hasdrawer'] = !empty($data['drawer']);
        return $data;
    }

    /**
     * Space switch tags shown to users who hold several rôles.
     *
     * @param string $active
     * @return array
     */
    protected function get_switch(string $active): array {
        $switch = [];
        if ($this->can_teach()) {
            $switch[] = $this->item('teacher', 'ph-chalkboard-teacher', new moodle_url('/local/alphatrade/teacher.php'),
                $active, get_string('space_teacher', 'theme_alphatrade'));
        }
        if ($this->is_partner()) {
            $switch[] = $this->item('partner', 'ph-handshake', new moodle_url('/local/alphatrade/partner.php'),
                $active, get_string('space_partner', 'theme_alphatrade'));
        }
        if ($this->is_admin()) {
            $switch[] = $this->item('adminhome', 'ph-gear-six', new moodle_url('/local/alphatrade/admin.php'),
                $active, get_string('space_admin', 'theme_alphatrade'));
        }
        return $switch;
    }

    /**
     * Élève : Accueil · Ma formation · Vidéos · Live · Communauté · Exercices · Mes résultats · Mon profil,
     * puis les outils du programme.
     *
     * @param string $active
     * @return array
     */
    protected function get_student_items(string $active): array {
        $urls = theme_alphatrade_has_app() ? [
            'home' => '/my/',
            'parcours' => '/local/alphatrade/parcours.php',
            'videos' => '/local/alphatrade/videos.php',
            'live' => '/local/alphatrade/live.php',
            'community' => '/local/alphatrade/community.php',
            'exercices' => '/local/alphatrade/exercices.php',
            'resultats' => '/local/alphatrade/resultats.php',
            'practice' => '/local/alphatrade/practice.php',
            'backtest' => '/local/alphatrade/backtesting.php',
            'journal' => '/local/alphatrade/journal.php',
            'tools' => '/local/alphatrade/tools.php',
            'resources' => '/local/alphatrade/resources.php',
            'referral' => '/local/alphatrade_referral/index.php',
            'profile' => '/local/alphatrade/profile.php',
        ] : [
            'home' => '/my/',
            'parcours' => '/my/courses.php',
            'profile' => '/user/profile.php',
        ];
        // Le parrainage n'apparait que si son plugin est installe.
        if (!class_exists('\local_alphatrade_referral\local\engine')) {
            unset($urls['referral']);
        }
        $items = [];
        foreach ($urls as $key => $path) {
            $item = $this->item($key, self::ICONS[$key] ?? '', new moodle_url($path), $active);
            $item['svg'] = self::SVG[$key] ?? '';
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Enseignant : Accueil · Mes formations · Vidéos · Live · Communauté · Mes élèves · Créer · À corriger ·
     * Résultats · Mon profil (+ les backtests des élèves, spécifiques Alpha Trade).
     *
     * @param string $active
     * @return array
     */
    protected function get_teacher_items(string $active): array {
        if ($active === 'community') {
            $active = 'teachercommunity';
        }
        $programmeid = (int) get_config('local_alphatrade', 'programmecourse');
        return [
            $this->item('teacher', 'ph-house', new moodle_url('/local/alphatrade/teacher.php'), $active),
            $this->item('teachercourses', 'ph-graduation-cap', $programmeid
                ? new moodle_url('/course/view.php', ['id' => $programmeid]) : new moodle_url('/my/courses.php'), $active),
            $this->item('videos', 'ph-play-circle', new moodle_url('/local/alphatrade/videos.php'), $active),
            $this->item('live', 'ph-broadcast', new moodle_url('/local/alphatrade/live.php'), $active),
            $this->item('teachercommunity', 'ph-users-three', new moodle_url('/local/alphatrade/community.php'), $active),
            $this->item('teacherstudents', 'ph-users-four', new moodle_url('/local/alphatrade/teacher.php',
                ['view' => 'students']), $active),
            $this->item('create', 'ph-plus-circle', new moodle_url('/local/alphatrade/create.php'), $active),
            $this->item('reviews', 'ph-file-text', new moodle_url('/local/alphatrade/reviews.php'), $active),
            $this->item('gradebook', 'ph-chart-bar', $programmeid
                ? new moodle_url('/grade/report/grader/index.php', ['id' => $programmeid])
                : new moodle_url('/grade/index.php'), $active),
            $this->item('studentbacktests', 'ph-chart-line', new moodle_url('/local/alphatrade/teacher.php',
                ['view' => 'backtests']), $active),
            $this->item('profile', 'ph-user', new moodle_url('/local/alphatrade/profile.php'), $active),
        ];
    }

    /**
     * Partenaire : Accueil · Mes élèves · Mes formations · Vidéos · Live · Communauté · Progression · Mon profil.
     * Lecture seule : le partenaire suit et oriente, il ne corrige jamais.
     *
     * @param string $active
     * @return array
     */
    protected function get_partner_items(string $active): array {
        return [
            $this->item('partner', 'ph-house', new moodle_url('/local/alphatrade/partner.php'), $active),
            $this->item('partnerstudents', 'ph-users-four', new moodle_url('/local/alphatrade/partner.php',
                ['view' => 'students']), $active),
            $this->item('partnercourses', 'ph-graduation-cap', new moodle_url('/local/alphatrade/partner.php',
                ['view' => 'courses']), $active),
            $this->item('videos', 'ph-play-circle', new moodle_url('/local/alphatrade/videos.php'), $active),
            $this->item('live', 'ph-broadcast', new moodle_url('/local/alphatrade/live.php'), $active),
            $this->item('community', 'ph-users-three', new moodle_url('/local/alphatrade/community.php'), $active),
            $this->item('partnerprogress', 'ph-chart-bar', new moodle_url('/local/alphatrade/partner.php',
                ['view' => 'progress']), $active),
            $this->item('profile', 'ph-user', new moodle_url('/local/alphatrade/profile.php'), $active),
        ];
    }

    /**
     * Administrateur : Dashboard · Formations · Vidéos · Lives · Communauté · Catégories · Utilisateurs ·
     * Contenus · Évaluations · Certifications · Statistiques · Journal · Paramètres.
     *
     * @param string $active
     * @return array
     */
    protected function get_admin_items(string $active): array {
        $admin = function(string $view = '') {
            return new moodle_url('/local/alphatrade/admin.php', $view ? ['view' => $view] : []);
        };
        return [
            $this->item('adminhome', 'ph-squares-four', $admin(), $active),
            $this->item('admincourses', 'ph-graduation-cap', $admin('courses'), $active),
            $this->item('videos', 'ph-play-circle', new moodle_url('/local/alphatrade/videos.php'), $active),
            $this->item('live', 'ph-broadcast', new moodle_url('/local/alphatrade/live.php'), $active),
            $this->item('community', 'ph-users-three', new moodle_url('/local/alphatrade/community.php'), $active),
            $this->item('admincategories', 'ph-folders', new moodle_url('/course/index.php'), $active),
            $this->item('adminusers', 'ph-users-three', $admin('users'), $active),
            $this->item('admincontents', 'ph-files', $admin('contents'), $active),
            $this->item('adminassessments', 'ph-check-square', $admin('assessments'), $active),
            $this->item('admincertifications', 'ph-certificate', $admin('certifications'), $active),
            $this->item('adminstats', 'ph-chart-bar', $admin('reports'), $active),
            $this->item('adminlog', 'ph-scroll', new moodle_url('/report/log/index.php', ['id' => SITEID]), $active),
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
        return new moodle_url('/my/');
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
     * Partner following students without being able to edit anything?
     *
     * @return bool
     */
    protected function is_partner(): bool {
        $programmeid = (int) get_config('local_alphatrade', 'programmecourse');
        $context = $programmeid ? context_course::instance($programmeid, IGNORE_MISSING) : null;
        return $this->can_follow() && $context && !has_capability('local/alphatrade:viewteacher', $context);
    }

    /**
     * Allowed to open the partner space? Partners get it as their own space; trainers and admins
     * only see it while they are actually on one of its pages.
     *
     * @return bool
     */
    protected function can_follow(): bool {
        if (!theme_alphatrade_has_app() || !isloggedin() || isguestuser()) {
            return false;
        }
        $programmeid = (int) get_config('local_alphatrade', 'programmecourse');
        $context = $programmeid ? context_course::instance($programmeid, IGNORE_MISSING) : null;
        return $context && has_capability('local/alphatrade:viewpartner', $context);
    }

    /**
     * The space the user was in last, if they still hold it. Pages shared by several spaces
     * (Vidéos, Live, Communauté, Mon profil) keep that space instead of falling back to élève.
     *
     * @return string student|teacher|partner|admin
     */
    protected function remembered_space(): string {
        $space = isloggedin() && !isguestuser() ? get_user_preferences('theme_alphatrade_space', 'student') : 'student';
        $holds = [
            'teacher' => function() {
                return $this->can_teach();
            },
            'partner' => function() {
                return $this->can_follow();
            },
            'admin' => function() {
                return $this->is_admin();
            },
        ];
        return isset($holds[$space]) && $holds[$space]() ? $space : 'student';
    }

    /**
     * Remember the space of the current page, so the shared pages stay in it.
     *
     * @param string $variant
     */
    protected function remember_space(string $variant): void {
        if (!isloggedin() || isguestuser() || $variant === get_user_preferences('theme_alphatrade_space', 'student')) {
            return;
        }
        set_user_preference('theme_alphatrade_space', $variant);
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
        if (strpos($pagetype, 'course-index') === 0 || strpos($pagetype, 'course-management') === 0) {
            return 'admincategories';
        }
        if (strpos($pagetype, 'report-log') !== false || strpos($path, '/report/log/') === 0) {
            return 'adminlog';
        }
        if (strpos($pagetype, 'cohort-') === 0) {
            return 'adminusers';
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
