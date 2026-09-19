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
 * Espace Enseignant, écran « Créer » : un point d'entrée unique par type de contenu
 * (leçon, vidéo, support, exercice, quiz, devoir, live), avec le choix du module.
 * Chaque bouton ouvre le formulaire natif Moodle déjà positionné sur la bonne section.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

$section = optional_param('section', 0, PARAM_INT);

page::setup('/local/alphatrade/create.php', 'create', get_string('create_title', 'local_alphatrade'),
    $section ? ['section' => $section] : [], get_string('sub_create', 'local_alphatrade'));

$course = programme::get_course();
if (!$course) {
    throw new moodle_exception('noprogrammecourse', 'local_alphatrade');
}
$context = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $context);

// Modules of the programme: the teacher picks the one the new content belongs to.
$programme = programme::for_user($USER->id);
$modules = [];
foreach ($programme->get_modules() as $module) {
    $modules[] = [
        'sectionnum' => $module['sectionnum'],
        'number' => $module['number'],
        'name' => programme::module_title($module),
        'itemcount' => count($module['items']),
        'selected' => $section == $module['sectionnum'],
    ];
}
if (!$section && $modules) {
    $current = $programme->get_current_module();
    $section = $current ? $current['sectionnum'] : $modules[0]['sectionnum'];
    foreach ($modules as &$module) {
        $module['selected'] = $module['sectionnum'] == $section;
    }
    unset($module);
}

/**
 * Link to the native "add an activity" form, already pointed at the right section.
 *
 * @param string $modname
 * @param int $courseid
 * @param int $section
 * @return string
 */
function local_alphatrade_addurl(string $modname, int $courseid, int $section): string {
    return (new moodle_url('/course/modedit.php', [
        'add' => $modname,
        'course' => $courseid,
        'section' => $section,
        'return' => 0,
        'sr' => 0,
    ]))->out(false);
}

$types = [
    ['key' => 'lesson', 'modname' => 'page', 'icon' => 'ph-article'],
    ['key' => 'video', 'modname' => 'url', 'icon' => 'ph-play-circle'],
    ['key' => 'support', 'modname' => 'resource', 'icon' => 'ph-file-text'],
    ['key' => 'exercise', 'modname' => 'assign', 'icon' => 'ph-clipboard-text'],
    ['key' => 'quiz', 'modname' => 'quiz', 'icon' => 'ph-exam'],
    ['key' => 'forum', 'modname' => 'forum', 'icon' => 'ph-chats-circle'],
];
$cards = [];
foreach ($types as $type) {
    if (!$DB->record_exists('modules', ['name' => $type['modname'], 'visible' => 1])) {
        continue;
    }
    $cards[] = [
        'title' => get_string('create_' . $type['key'], 'local_alphatrade'),
        'text' => get_string('create_' . $type['key'] . '_desc', 'local_alphatrade'),
        'icon' => $type['icon'],
        'url' => local_alphatrade_addurl($type['modname'], (int) $course->id, (int) $section),
    ];
}

$data = [
    'modules' => $modules,
    'hasmodules' => !empty($modules),
    'cards' => $cards,
    'section' => $section,
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    'moduleurl' => (new moodle_url('/local/alphatrade/module.php', ['section' => $section]))->out(false),
    'liveurl' => (new moodle_url('/calendar/view.php', ['view' => 'month', 'course' => $course->id]))->out(false),
    'formurl' => (new moodle_url('/local/alphatrade/create.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/create', $data);
echo $OUTPUT->footer();
