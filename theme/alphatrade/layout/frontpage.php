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
 * Site home. Visitors get the Alpha Trade public site, logged-in users the app shell.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ((isloggedin() && !isguestuser()) || !get_config('theme_alphatrade', 'publichome')) {
    require(__DIR__ . '/app.php');
    return;
}

$publicsite = new \theme_alphatrade\output\public_site(optional_param('view', '', PARAM_ALPHA));
$templatecontext = array_merge($publicsite->export(), [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(['alpha-public']),
    'markurl' => $OUTPUT->image_url('mark', 'theme_alphatrade')->out(false),
    'logourl' => $OUTPUT->image_url('logo', 'theme_alphatrade')->out(false),
    'heroimageurl' => $OUTPUT->image_url('hero', 'theme_alphatrade')->out(false),
    'loginurl' => get_login_url(),
]);

echo $OUTPUT->render_from_template('theme_alphatrade/public', $templatecontext);
