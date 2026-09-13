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
 * Teacher: chart analyses to correct.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core_user\fields;
use local_alphatrade\local\page;

$filter = optional_param('filter', 'submitted', PARAM_ALPHA);
$filter = in_array($filter, ['submitted', 'reviewed', 'all']) ? $filter : 'submitted';

page::setup('/local/alphatrade/reviews.php', 'reviews', get_string('reviews', 'local_alphatrade'), ['filter' => $filter]);
require_capability('local/alphatrade:reviewanalysis', page::programme_context());

$userfields = fields::for_name()->get_sql('u', false, '', '', false)->selects;
$where = $filter === 'all' ? '' : 'WHERE a.status = :status';
$sql = "SELECT a.id, a.status, a.score, a.timemodified, a.userid, c.title, c.symbol, c.timeframe, $userfields
          FROM {local_alphatrade_analysis} a
          JOIN {local_alphatrade_chart} c ON c.id = a.chartid
          JOIN {user} u ON u.id = a.userid
          $where
      ORDER BY a.status DESC, a.timemodified ASC";
$records = $DB->get_records_sql($sql, $filter === 'all' ? [] : ['status' => $filter], 0, 500);

$rows = [];
foreach ($records as $record) {
    $reviewed = $record->status === 'reviewed';
    $rows[] = [
        'student' => fullname($record),
        'chart' => format_string($record->title),
        'meta' => page::clean_symbol($record->symbol) . ' · ' . page::timeframe_label($record->timeframe),
        'date' => userdate($record->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
        'isreviewed' => $reviewed,
        'score' => $reviewed ? (int) $record->score : '',
        'url' => (new moodle_url('/local/alphatrade/review.php', ['id' => $record->id]))->out(false),
    ];
}

$filters = [];
foreach (['submitted', 'reviewed', 'all'] as $key) {
    $filters[] = [
        'label' => get_string('filter_' . $key, 'local_alphatrade'),
        'url' => (new moodle_url('/local/alphatrade/reviews.php', ['filter' => $key]))->out(false),
        'active' => $key === $filter,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/reviews', [
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'filters' => $filters,
]);
echo $OUTPUT->footer();
