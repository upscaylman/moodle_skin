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
 * Alpha Trade library (wireframe v3 screen 12): activities of the "Ressources" course,
 * with search and type filters (PDF, videos, checklists, templates).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;

$type = optional_param('type', '', PARAM_ALPHA);
$types = ['pdf', 'video', 'checklist', 'template'];
$type = in_array($type, $types) ? $type : '';
$search = trim(optional_param('q', '', PARAM_TEXT));

page::setup('/local/alphatrade/resources.php', 'resources', get_string('resources', 'local_alphatrade'),
    array_filter(['type' => $type, 'q' => $search]), get_string('sub_resources', 'local_alphatrade'));

$items = [];
$courseid = (int) get_config('local_alphatrade', 'resourcescourse');
if ($courseid && $DB->record_exists('course', ['id' => $courseid])) {
    $course = get_course($courseid);
    $modinfo = get_fast_modinfo($course, $USER->id);
    foreach ($modinfo->get_section_info_all() as $section) {
        $sectionname = get_section_name($course, $section);
        foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            if (!$cm->uservisible || !$cm->url || !empty($cm->deletioninprogress) || $cm->modname === 'subsection') {
                continue;
            }
            $name = $cm->get_formatted_name();
            $haystack = core_text::strtolower($name . ' ' . $sectionname);
            $icon = (string) $cm->icon;

            $itemtypes = [];
            if (strpos($icon, 'f/pdf') === 0) {
                $itemtypes[] = 'pdf';
            }
            if (strpos($icon, 'f/video') === 0 || $cm->modname === 'h5pactivity') {
                $itemtypes[] = 'video';
            }
            if (strpos($haystack, 'checklist') !== false) {
                $itemtypes[] = 'checklist';
            }
            if (preg_match('/mod[eè]le|template/u', $haystack)) {
                $itemtypes[] = 'template';
            }
            if ($cm->modname === 'url' && !in_array('video', $itemtypes)) {
                $urlrecord = $DB->get_field('url', 'externalurl', ['id' => $cm->instance]);
                if ($urlrecord && preg_match('/youtube|youtu\.be|vimeo/i', $urlrecord)) {
                    $itemtypes[] = 'video';
                }
            }

            if ($type && !in_array($type, $itemtypes)) {
                continue;
            }
            if ($search !== '' && strpos($haystack, core_text::strtolower($search)) === false) {
                continue;
            }

            $isdownload = $cm->modname === 'resource';
            $typelabel = $itemtypes ? get_string('restype_' . $itemtypes[0], 'local_alphatrade')
                : get_string('modulename', 'mod_' . $cm->modname);
            $items[] = [
                'name' => $name,
                'meta' => $typelabel . ' · ' . $sectionname,
                'url' => $cm->url->out(false),
                'icon' => in_array('video', $itemtypes) ? 'ph-play-circle' : 'ph-file-text',
                'isdownload' => $isdownload,
                'isuse' => in_array('template', $itemtypes),
            ];
        }
    }
}

$baseurl = new moodle_url('/local/alphatrade/resources.php', $search !== '' ? ['q' => $search] : []);
$pills = [['label' => get_string('all', 'local_alphatrade'), 'url' => $baseurl->out(false), 'active' => $type === '']];
foreach ($types as $key) {
    $pills[] = [
        'label' => get_string('restypes_' . $key, 'local_alphatrade'),
        'url' => (new moodle_url($baseurl, ['type' => $key]))->out(false),
        'active' => $type === $key,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/resources', [
    'action' => (new moodle_url('/local/alphatrade/resources.php'))->out(false),
    'type' => $type,
    'q' => $search,
    'pills' => $pills,
    'items' => $items,
    'hasitems' => !empty($items),
    'configured' => (bool) $courseid,
]);
echo $OUTPUT->footer();
