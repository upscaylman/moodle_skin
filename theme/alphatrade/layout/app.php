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

// Students of the programme navigate with Alpha Trade, not with Moodle's course index.
$courseindex = core_course_drawer();
if ($courseindex && !$caneditcourse && $inprogramme) {
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

$searchurl = theme_alphatrade_has_app() ? new moodle_url('/local/alphatrade/resources.php') : new moodle_url('/course/search.php');

$templatecontext = [
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
    'isalphapage' => $isalphapage || $lesson,
    'isnativepage' => !$isalphapage && !$lesson,
    'title' => $title,
    'subtitle' => $subtitle,
    'standalone' => $standalone,
    'lesson' => $lesson,
    'nav' => $nav,
    'logourl' => $OUTPUT->image_url('logo', 'theme_alphatrade')->out(false),
    'markurl' => $OUTPUT->image_url('mark', 'theme_alphatrade')->out(false),
    'homeurl' => theme_alphatrade_has_app() ? (new moodle_url('/local/alphatrade/index.php'))->out(false)
        : (new moodle_url('/my/'))->out(false),
    'searchurl' => $searchurl->out(false),
    'userfullname' => isloggedin() ? fullname($USER) : '',
    'userrole' => $nav['isteacher'] ? get_string('role_teacher', 'theme_alphatrade') : get_string('role_admin', 'theme_alphatrade'),
    'userinitials' => isloggedin() ? core_text::strtoupper(core_text::substr($USER->firstname, 0, 1) . core_text::substr($USER->lastname, 0, 1)) : '',
    'profileurl' => (new moodle_url(theme_alphatrade_has_app() ? '/local/alphatrade/profile.php' : '/user/profile.php'))->out(false),
];

echo $OUTPUT->render_from_template($isstandalone ? 'theme_alphatrade/standalone' : 'theme_alphatrade/app', $templatecontext);
