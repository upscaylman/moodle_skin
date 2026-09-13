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
 * Alpha Trade app shell: fixed header, left sidebar (desktop), tab bar + menu (mobile).
 * Drawer logic is Boost's (drawers.php) so course index, blocks and editing keep working.
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

$isalphapage = strpos(' ' . $PAGE->bodyclasses . ' ', ' alpha-page ') !== false;
$caneditcourse = !empty($PAGE->course->id) && $PAGE->course->id != SITEID
    && has_capability('moodle/course:update', context_course::instance($PAGE->course->id));

$extraclasses = ['uses-drawers', 'alpha-app'];

// The course index is Moodle's navigation: students of the programme get the Alpha Trade one instead.
$courseindex = core_course_drawer();
$programmeid = theme_alphatrade_has_app() ? (int) get_config('local_alphatrade', 'programmecourse') : 0;
if ($courseindex && !$caneditcourse && $programmeid && $PAGE->course->id == $programmeid) {
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
if (!$hasblocks) {
    $blockdraweropen = false;
}

// Lesson reader and quiz: header "back to module + Lesson X / Y" and a single next action.
$lesson = null;
if (theme_alphatrade_has_app() && !empty($PAGE->cm) && $programmeid && $PAGE->course->id == $programmeid
        && !($caneditcourse && $PAGE->user_is_editing())) {
    $lesson = \local_alphatrade\local\programme::lesson_context($PAGE->cm, $USER->id);
    if ($lesson) {
        $extraclasses[] = $lesson['isquiz'] ? 'alpha-quiz' : 'alpha-lesson';
    }
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
// Students do not need Moodle's course tabs (Participants, Grades, Reports...) on programme pages.
if ($isalphapage || ($programmeid && $PAGE->course->id == $programmeid && !$caneditcourse && $PAGE->context->contextlevel != CONTEXT_MODULE)) {
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

$navigation = new \theme_alphatrade\output\navigation($PAGE);

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
    'showpageheader' => !$isalphapage && !$lesson,
    'lesson' => $lesson,
    'nav' => $navigation->export(),
    'logourl' => $OUTPUT->image_url('logo', 'theme_alphatrade')->out(false),
    'markurl' => $OUTPUT->image_url('mark', 'theme_alphatrade')->out(false),
    'homeurl' => theme_alphatrade_has_app() ? (new moodle_url('/local/alphatrade/index.php'))->out(false)
        : (new moodle_url('/my/'))->out(false),
];

echo $OUTPUT->render_from_template('theme_alphatrade/app', $templatecontext);
