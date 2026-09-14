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
 * robots.txt, sitemap.xml, llms.txt and llms-full.txt of the public site.
 *
 * Served at the web root by Apache (canonical virtual host):
 *   RewriteRule ^/(robots\.txt|sitemap\.xml|llms\.txt|llms-full\.txt)$ /theme/alphatrade/seo.php?file=$1 [PT,L]
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- public files for crawlers.
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

use theme_alphatrade\output\seo;

$file = optional_param('file', '', PARAM_PATH);
$types = [
    'robots.txt' => 'text/plain',
    'sitemap.xml' => 'application/xml',
    'llms.txt' => 'text/markdown',
    'llms-full.txt' => 'text/markdown',
];
if (!isset($types[$file])) {
    send_header_404();
    die();
}

$PAGE->set_context(context_system::instance());

switch ($file) {
    case 'robots.txt':
        $content = seo::robots();
        break;
    case 'sitemap.xml':
        $content = seo::sitemap();
        break;
    default:
        $content = seo::llms($file === 'llms-full.txt');
}

header('Content-Type: ' . $types[$file] . '; charset=utf-8');
header('Cache-Control: public, max-age=3600');
if ($file === 'robots.txt' || $file === 'sitemap.xml') {
    header('X-Robots-Tag: noindex');
}
echo $content;
