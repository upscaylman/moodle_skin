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
    $files = ['fonts', 'icons', 'nocturne', 'shell', 'native', 'quiz', 'screens', 'staff', 'login', 'public', 'messaging', 'billing', 'dashboard', 'settings', 'notifications', 'preferences', 'filepicker', 'editor', 'tables', 'pillars', 'courseindex'];
    $scss = '';
    foreach ($files as $file) {
        $scss .= file_get_contents(__DIR__ . "/scss/post/_{$file}.scss") . "\n";
    }
    return $scss;
}

/**
 * Langues de l'interface, pour le sélecteur du bandeau (app, site public, connexion).
 * Une seule langue installée : rien à afficher, le sélecteur disparaît.
 *
 * @return array
 */
function theme_alphatrade_languages(): array {
    global $PAGE;

    $translations = get_string_manager()->get_list_of_translations();
    if (count($translations) < 2) {
        return ['haslangs' => false, 'langs' => [], 'currentlang' => '', 'currentlangname' => ''];
    }
    $current = current_language();
    $base = $PAGE->url ?: new moodle_url('/');
    $langs = [];
    foreach ($translations as $code => $name) {
        $url = new moodle_url($base);
        $url->param('lang', $code);
        $langs[] = [
            'code' => $code,
            'short' => core_text::strtoupper(explode('_', $code)[0]),
            // Moodle encadre le code de marques de direction : elles n'ont pas leur place ici.
            'name' => trim(preg_replace('/\s*\x{200E}?\(.*$/u', '', $name)),
            'url' => $url->out(false),
            'active' => $code === $current,
        ];
    }
    return [
        'haslangs' => true,
        'langs' => $langs,
        'currentlang' => core_text::strtoupper(explode('_', $current)[0]),
        'currentlangname' => $translations[$current] ?? $current,
    ];
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
