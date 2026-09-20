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
 * Alpha Trade app shell (maquettes Portail Etudiant / Espace Enseignant / Espace Admin):
 * full-height sidebar, in-page header (title, subtitle, search, notifications, avatar),
 * mobile top bar + bottom tab bar. Boost drawers (course index, blocks) keep working.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}
if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$bodyclasses = ' ' . $PAGE->bodyclasses . ' ';
$isalphapage = strpos($bodyclasses, ' alpha-page ') !== false;
$isstandalone = strpos($bodyclasses, ' alpha-standalone ') !== false;
$caneditcourse = $PAGE->course->id != SITEID
    && has_capability('moodle/course:update', context_course::instance($PAGE->course->id));
$programmeid = theme_alphatrade_has_app() ? (int) get_config('local_alphatrade', 'programmecourse') : 0;
$inprogramme = $programmeid && $PAGE->course->id == $programmeid;

$extraclasses = ['uses-drawers', 'alpha-app'];

// Single dashboard: Moodle's /my/ (Home link) carries the Alpha Trade dashboard above its Timeline and Calendar blocks.
$isdashboard = strpos($PAGE->pagetype, 'my-index') === 0 && isloggedin() && !isguestuser();
if ($isdashboard) {
    $extraclasses[] = 'alpha-dashboard';
}

// The Alpha Trade sidebar is the only navigation of the app shell: Moodle's course index drawer is dropped
// everywhere, and kept only for a teacher who turned editing on and moves activities with it.
$courseindex = core_course_drawer();
if ($courseindex && !($caneditcourse && $PAGE->user_is_editing())) {
    $courseindex = '';
}
if (!$courseindex) {
    $courseindexopen = false;
}
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if ($isalphapage && !$PAGE->user_is_editing()) {
    $hasblocks = false;
}
if (!$hasblocks) {
    $blockdraweropen = false;
}
// Site administration pages (admin layout, and site-level reports such as Config changes) keep their full width for
// tables and forms: the block drawer (Admin bookmarks) starts closed there, whatever the saved preference. Its toggle
// stays at the right edge of the page.
$isadminpage = $PAGE->pagelayout === 'admin'
    || ($PAGE->pagelayout === 'report' && $PAGE->context->contextlevel == CONTEXT_SYSTEM);
if ($isadminpage) {
    $blockdraweropen = false;
}

// Moodle titles admin pages with the site name: show the page name instead, the first part of the window title
// ("Browse list of users | Accounts | Users | Administration | Site"). The breadcrumb is not read here: building it
// from the layout duplicates its last item.
$sitenames = [format_string($SITE->fullname), format_string($SITE->shortname)];
if (!$isalphapage && $isadminpage && in_array($PAGE->heading, $sitenames, true)) {
    $pagename = trim(explode(moodle_page::TITLE_SEPARATOR, $PAGE->title)[0]);
    if ($pagename !== '' && !in_array($pagename, $sitenames, true)) {
        $PAGE->set_heading($pagename, false);
    }
}

// Lesson reader and quiz (maquette screens "Leçon" and "Évaluation").
$lesson = null;
if (theme_alphatrade_has_app() && $PAGE->cm && $inprogramme && !($caneditcourse && $PAGE->user_is_editing())) {
    $lesson = \local_alphatrade\local\programme::lesson_context($PAGE->cm, $USER->id);
    if ($lesson) {
        $extraclasses[] = $lesson['isquiz'] ? 'alpha-quiz' : 'alpha-lesson';
        if ($lesson['isquiz']) {
            $lesson = array_merge($lesson, \local_alphatrade\local\quizview::context($PAGE, $PAGE->cm, $USER->id));
        }
    }
}

// Maquette v4 : "Lecon" garde son rail plat dans la colonne de droite, "Evaluation" n'a aucun rail.
// Le tiroir de blocs de Moodle (navigation du test, etc.) reste donc ferme sur ces deux ecrans.
if ($lesson && !$PAGE->user_is_editing()) {
    $hasblocks = false;
    $blockdraweropen = false;
}

$navigation = new \theme_alphatrade\output\navigation($PAGE);
$nav = $navigation->export();
if (!$nav['isstudent']) {
    $extraclasses[] = 'alpha-staff';
}
if ($isstandalone) {
    $extraclasses[] = 'alpha-standalone-layout';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}
if ($isalphapage || $lesson || ($inprogramme && !$caneditcourse && $PAGE->context->contextlevel != CONTEXT_MODULE)) {
    $secondarynavigation = false;
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
if (!empty($primarymenu['user']) && isloggedin() && !isguestuser()) {
    $primarymenu['user']['alphafirstname'] = format_string($USER->firstname);
}
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

// In-page header: Alpha Trade pages declare title + subtitle, Moodle pages use their heading.
$title = format_string($PAGE->heading ?: $PAGE->title);
$subtitle = '';
$standalone = [];
if (theme_alphatrade_has_app()) {
    $declared = \local_alphatrade\local\page::get_header();
    if ($declared) {
        $title = $declared['title'];
        $subtitle = $declared['subtitle'];
        $standalone = $declared['standalone'] ?? [];
    }
}
if ($lesson) {
    $title = $lesson['isquiz'] ? get_string('evaluation', 'theme_alphatrade') : get_string('lesson', 'theme_alphatrade');
    $subtitle = $lesson['isquiz'] ? get_string('evaluation_sub', 'theme_alphatrade') : $lesson['modulename'] . '.';
}

$dashboard = '';
if ($isdashboard) {
    $title = get_string('nav_home', 'theme_alphatrade');
    $subtitle = theme_alphatrade_has_app() ? get_string('sub_home', 'local_alphatrade') : '';
    if (theme_alphatrade_has_app() && \local_alphatrade\local\dashboard::enabled()) {
        $dashboard = $OUTPUT->render_from_template('local_alphatrade/dashboard', \local_alphatrade\local\dashboard::export($USER));
    }
}

// Header search: resources for students, settings search for the admin space, course search for trainers.
$searchurl = theme_alphatrade_has_app() ? new moodle_url('/local/alphatrade/resources.php') : new moodle_url('/course/search.php');
if (!$nav['isstudent']) {
    $searchurl = !empty($nav['isadmin']) ? new moodle_url('/admin/search.php') : new moodle_url('/course/search.php');
}

$templatecontext = theme_alphatrade_languages() + [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'secondarymoremenu' => $secondarynavigation ?: false,
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'isalphapage' => $isalphapage || $lesson || $isdashboard,
    'isnativepage' => !$isalphapage && !$lesson && !$isdashboard,
    'dashboard' => $dashboard,
    'headingbutton' => $isdashboard && $PAGE->user_is_editing() ? $OUTPUT->page_heading_button() : '',
    'title' => $title,
    'subtitle' => $subtitle,
    'standalone' => $standalone,
    'pubnav' => $standalone['links'] ?? [],
    'haspubnav' => !empty($standalone['links']),
    'lesson' => $lesson,
    'nav' => $nav,
    'logourl' => $OUTPUT->image_url('logo', 'theme_alphatrade')->out(false),
    'markurl' => $OUTPUT->image_url('mark', 'theme_alphatrade')->out(false),
    'homeurl' => (new moodle_url('/my/'))->out(false),
    'searchurl' => $searchurl->out(false),
    'userfullname' => isloggedin() ? fullname($USER) : '',
    'userrole' => $nav['isteacher'] ? get_string('role_teacher', 'theme_alphatrade') : get_string('role_admin', 'theme_alphatrade'),
    'userinitials' => isloggedin() ? core_text::strtoupper(core_text::substr($USER->firstname, 0, 1) . core_text::substr($USER->lastname, 0, 1)) : '',
    'profileurl' => (new moodle_url(theme_alphatrade_has_app() ? '/local/alphatrade/profile.php' : '/user/profile.php'))->out(false),
];

echo $OUTPUT->render_from_template($isstandalone ? 'theme_alphatrade/standalone' : 'theme_alphatrade/app', $templatecontext);
