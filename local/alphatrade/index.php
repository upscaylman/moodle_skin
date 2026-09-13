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
 * Student dashboard (maquette Portail Etudiant, screen "Accueil").
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\activity;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/index.php', 'home', get_string('nav_home', 'theme_alphatrade'), [],
    get_string('sub_home', 'local_alphatrade'));

$streak = activity::get_streak($USER->id);
$data = [
    'greeting' => get_string('greeting', 'local_alphatrade', format_string($USER->firstname)),
    'hasprogramme' => false,
    'isadmin' => has_capability('moodle/site:config', context_system::instance()),
    'settingsurl' => (new moodle_url('/admin/settings.php', ['section' => 'local_alphatrade']))->out(false),
    'streak' => get_string('streakdays', 'local_alphatrade', $streak),
    'trades' => activity::count_backtested_trades($USER->id),
    'badges' => activity::count_badges($USER->id),
];

$programme = programme::for_user($USER->id);
if ($programme) {
    $summary = $programme->get_summary();
    $next = $programme->get_next();

    $data['hasprogramme'] = true;
    $data['percent'] = $summary['percent'];
    $data['week'] = get_string('weekcounter', 'local_alphatrade', ['week' => $summary['week'], 'weeks' => $summary['weeks']]);

    if ($next) {
        $module = $next['module'];
        $item = $next['item'];
        $itemlabel = get_string('openmodule', 'local_alphatrade');
        if ($item) {
            $number = '';
            foreach ($module['lessons'] as $lesson) {
                if ($lesson['cmid'] == $item['cmid']) {
                    $number = $lesson['number'];
                }
            }
            $itemlabel = $item['isquiz'] ? get_string('evaluation', 'local_alphatrade') . ' - ' . $item['name']
                : get_string('lessonnumbered', 'local_alphatrade', ['number' => $number, 'name' => $item['name']]);
        }
        $data['continue'] = [
            'title' => get_string('modulelabel', 'local_alphatrade', $module['number']) . ' - ' . $module['name'],
            'item' => $itemlabel,
            'url' => $item ? $item['url'] : $module['url'],
            'cta' => $item && $item['isquiz'] ? get_string('startevaluation', 'local_alphatrade')
                : get_string('continuelesson', 'local_alphatrade'),
        ];
    } else if ($summary['iscomplete']) {
        $data['continue'] = [
            'title' => get_string('programmedone', 'local_alphatrade'),
            'item' => get_string('programmedone_title', 'local_alphatrade'),
            'url' => (new moodle_url('/local/alphatrade/project.php'))->out(false),
            'cta' => get_string('finalproject', 'local_alphatrade'),
        ];
    }

    $data['modules'] = array_map(function($module) {
        return [
            'number' => $module['number'],
            'name' => $module['name'],
            'url' => $module['url'],
            'islocked' => $module['islocked'],
            'iconclass' => programme::maquette_icon($module['status']),
            'statusclass' => programme::maquette_status_class($module['status']),
            'statuslabel' => $module['statuslabel'],
        ];
    }, $programme->get_modules());
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/dashboard', $data);
echo $OUTPUT->footer();
