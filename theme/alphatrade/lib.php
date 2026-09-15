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
 * Alpha Trade theme callbacks.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Default accent: matte gold derived from the logo, deliberately desaturated. */
define('THEME_ALPHATRADE_ACCENT', '#c99a3e');

/**
 * Main SCSS: Boost's default preset, untouched. Alpha Trade only changes variables (pre) and
 * adds component styles (extra), so Boost upgrades keep working.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_alphatrade_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

/**
 * Variables injected before Bootstrap/Boost compile.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_alphatrade_get_pre_scss($theme) {
    $accent = $theme->settings->accentcolor ?? '';
    if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $accent)) {
        $accent = THEME_ALPHATRADE_ACCENT;
    }
    $accent = strtolower($accent);
    return '$alpha-accent: ' . $accent . ";\n" . file_get_contents(__DIR__ . '/scss/pre.scss');
}

/**
 * Component styles appended after Boost compiles.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_alphatrade_get_extra_scss($theme) {
    $files = ['fonts', 'icons', 'nocturne', 'shell', 'native', 'quiz', 'screens', 'staff', 'login', 'public', 'messaging', 'billing', 'dashboard', 'settings', 'notifications', 'preferences', 'filepicker'];
    $scss = '';
    foreach ($files as $file) {
        $scss .= file_get_contents(__DIR__ . "/scss/post/_{$file}.scss") . "\n";
    }
    return $scss;
}

/**
 * Is the Alpha Trade app plugin (pages, data, programme mapping) installed?
 *
 * @return bool
 */
function theme_alphatrade_has_app(): bool {
    static $hasapp = null;
    if ($hasapp === null) {
        $hasapp = class_exists('\local_alphatrade\local\programme');
    }
    return $hasapp;
}
