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
 * Module page (maquette Portail Etudiant, screen "Module").
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

$sectionnum = required_param('section', PARAM_INT);

page::setup('/local/alphatrade/module.php', 'parcours', get_string('parcours', 'local_alphatrade'), ['section' => $sectionnum]);

$programme = programme::for_user($USER->id);
$module = $programme ? $programme->get_module($sectionnum) : null;
if (!$module) {
    redirect(new moodle_url('/local/alphatrade/parcours.php'));
}

$label = get_string('modulelabel', 'local_alphatrade', $module['number']);
page::set_header($label, $module['name'] . '.');

$rows = [];
$lessonnumbers = [];
foreach ($module['lessons'] as $lesson) {
    $lessonnumbers[$lesson['cmid']] = $lesson['number'];
}
$position = 0;
foreach ($module['items'] as $item) {
    $position++;
    $rows[] = [
        'number' => sprintf('%02d', $position),
        'name' => $item['name'],
        'url' => $item['url'],
        'islocked' => $item['islocked'],
        'iscurrent' => $item['iscurrent'],
        'iconclass' => programme::maquette_icon($item['status']),
        'statusclass' => programme::maquette_status_class($item['status']),
        'availableinfo' => $item['availableinfo'],
    ];
}

$description = trim(html_entity_decode(strip_tags($module['description']), ENT_QUOTES, 'UTF-8'));

$data = [
    'parcoursurl' => (new moodle_url('/local/alphatrade/parcours.php'))->out(false),
    'label' => core_text::strtoupper($label),
    'name' => $module['name'],
    'description' => $description,
    'percent' => $module['percent'],
    'lessonsprogress' => get_string('lessonsshort', 'local_alphatrade',
        ['done' => $module['lessonsdone'], 'total' => $module['lessoncount']]),
    'rows' => $rows,
    'islocked' => $module['islocked'],
    'availableinfo' => $module['availableinfo'],
    'hasobjectives' => $module['hasobjectives'],
    'objectives' => $module['objectives'],
    'objectivesdone' => $module['isdone'],
    'canedit' => has_capability('moodle/course:update', $programme->get_context()),
    'editurl' => (new moodle_url('/course/section.php', ['id' => $module['id']]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/module', $data);
echo $OUTPUT->footer();
