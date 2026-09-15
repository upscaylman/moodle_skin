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
 * Alpha Trade theme config. Child of Boost: Boost keeps the layout logic (drawers, JS),
 * Alpha Trade replaces the shell (header, sidebar, tab bar), the login and the palette.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'alphatrade';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
// TinyMCE editing area in the dark theme (scss/editor.scss); the editor's toolbar and menus: scss/post/_editor.scss.
$THEME->editor_scss = ['editor'];
$THEME->scss = function($theme) {
    return theme_alphatrade_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_alphatrade_get_pre_scss';
$THEME->extrascsscallback = 'theme_alphatrade_get_extra_scss';

// Every logged-in page goes through the Alpha Trade app shell. Layouts not listed here
// (popup, frametop, embedded, maintenance, print, redirect, secure) are inherited from Boost.
$THEME->layouts = [
    'base' => ['file' => 'app.php', 'regions' => []],
    'standard' => ['file' => 'app.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
    'course' => [
        'file' => 'app.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['langmenu' => true],
    ],
    'coursecategory' => ['file' => 'app.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
    'incourse' => ['file' => 'app.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
    'frontpage' => [
        'file' => 'frontpage.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true],
    ],
    'admin' => ['file' => 'app.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
    'mycourses' => [
        'file' => 'app.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true],
    ],
    'mydashboard' => [
        'file' => 'app.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true, 'langmenu' => true],
    ],
    'mypublic' => ['file' => 'app.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
    'login' => ['file' => 'login.php', 'regions' => [], 'options' => ['langmenu' => true]],
    'report' => ['file' => 'app.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
];

$THEME->enable_dock = false;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->activityheaderconfig = [
    'notitle' => true,
];
