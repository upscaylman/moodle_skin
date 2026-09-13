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
 * Student dashboard (wireframe v3 screen 1).
 * Visual priority: Continue > Progress > Programme > personal stats.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\activity;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/index.php', 'home', get_string('dashboard', 'local_alphatrade'));

$data = [
    'greeting' => get_string('greeting', 'local_alphatrade', format_string($USER->firstname)),
    'hasprogramme' => false,
    'isadmin' => has_capability('moodle/site:config', context_system::instance()),
    'settingsurl' => (new moodle_url('/admin/settings.php', ['section' => 'local_alphatrade']))->out(false),
    'stats' => [
        'streak' => activity::get_streak($USER->id),
        'trades' => activity::count_backtested_trades($USER->id),
        'badges' => activity::count_badges($USER->id),
        'backtestingurl' => (new moodle_url('/local/alphatrade/backtesting.php'))->out(false),
        'profileurl' => (new moodle_url('/local/alphatrade/profile.php'))->out(false),
    ],
];

$programme = programme::for_user($USER->id);
if ($programme) {
    $summary = $programme->get_summary();
    $next = $programme->get_next();

    $data['hasprogramme'] = true;
    $data['progress'] = [
        'percent' => $summary['percent'],
        'week' => get_string('weekcounter', 'local_alphatrade', ['week' => $summary['week'], 'weeks' => $summary['weeks']]),
        'modules' => get_string('modulecounter', 'local_alphatrade',
            ['done' => $summary['modulesdone'], 'total' => $summary['modulestotal']]),
        'lessons' => get_string('lessonsdonecounter', 'local_alphatrade',
            ['done' => $summary['lessonsdone'], 'total' => $summary['lessonstotal']]),
    ];

    if ($next && $next['item']) {
        $data['continue'] = [
            'modulelabel' => get_string('modulelabel', 'local_alphatrade', $next['module']['number']),
            'modulename' => $next['module']['name'],
            'itemname' => $next['item']['name'],
            'icon' => $next['item']['icon'],
            'url' => $next['item']['url'],
            'isquiz' => $next['item']['isquiz'],
            'modulepercent' => $next['module']['percent'],
        ];
    } else if ($next) {
        $data['continue'] = [
            'modulelabel' => get_string('modulelabel', 'local_alphatrade', $next['module']['number']),
            'modulename' => $next['module']['name'],
            'itemname' => get_string('openmodule', 'local_alphatrade'),
            'icon' => 'ph-path',
            'url' => $next['module']['url'],
            'isquiz' => false,
            'modulepercent' => $next['module']['percent'],
        ];
    } else if ($summary['iscomplete']) {
        $data['finished'] = [
            'projecturl' => (new moodle_url('/local/alphatrade/project.php'))->out(false),
            'certificateurl' => (new moodle_url('/local/alphatrade/certificate.php'))->out(false),
        ];
    }

    $data['modules'] = array_map(function($module) {
        return [
            'number' => $module['number'],
            'name' => $module['name'],
            'url' => $module['url'],
            'percent' => $module['percent'],
            'isdone' => $module['isdone'],
            'iscurrent' => $module['iscurrent'],
            'islocked' => $module['islocked'],
            'statusicon' => $module['statusicon'],
            'statuslabel' => $module['statuslabel'],
        ];
    }, $programme->get_modules());
    $data['parcoursurl'] = (new moodle_url('/local/alphatrade/parcours.php'))->out(false);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/dashboard', $data);
echo $OUTPUT->footer();
