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
 * Correction queue (maquette Espace Enseignant, screen "Devoirs à corriger"):
 * Moodle assignments and Practice Lab chart analyses.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\reviewqueue;

$filter = optional_param('filter', 'submitted', PARAM_ALPHA);
$filter = in_array($filter, ['submitted', 'reviewed', 'all']) ? $filter : 'submitted';

page::setup('/local/alphatrade/reviews.php', 'reviews', get_string('reviews', 'local_alphatrade'), ['filter' => $filter],
    get_string('sub_reviews', 'local_alphatrade'));
require_capability('local/alphatrade:reviewanalysis', page::programme_context());

$filters = [];
foreach (['submitted', 'reviewed', 'all'] as $key) {
    $filters[] = [
        'label' => get_string('filter_' . $key, 'local_alphatrade'),
        'url' => (new moodle_url('/local/alphatrade/reviews.php', ['filter' => $key]))->out(false),
        'active' => $key === $filter,
    ];
}
$rows = reviewqueue::items($filter, 300);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/reviews', [
    'filters' => $filters,
    'rows' => $rows,
    'hasrows' => !empty($rows),
]);
echo $OUTPUT->footer();
