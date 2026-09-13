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
 * Chart analysis exercises (Practice Lab > Graphiques).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;

page::setup('/local/alphatrade/charts.php', 'practice', get_string('practice_charts', 'local_alphatrade'));

$canmanage = page::is_staff('local/alphatrade:managecharts');
$select = $canmanage ? '' : 'visible = 1';
$charts = $DB->get_records_select('local_alphatrade_chart', $select, [], 'sortorder ASC, id ASC');
$submissions = $DB->get_records('local_alphatrade_analysis', ['userid' => $USER->id], '', 'chartid, status, score');

$items = [];
foreach ($charts as $chart) {
    $submission = $submissions[$chart->id] ?? null;
    if (!$submission) {
        $status = ['label' => get_string('analysis_todo', 'local_alphatrade'), 'class' => 'neutral', 'icon' => 'ph-circle'];
    } else if ($submission->status === 'reviewed') {
        $status = ['label' => get_string('analysis_reviewed', 'local_alphatrade', (int) $submission->score),
            'class' => 'success', 'icon' => 'ph-check-circle'];
    } else {
        $status = ['label' => get_string('analysis_submitted', 'local_alphatrade'), 'class' => 'warning', 'icon' => 'ph-hourglass-medium'];
    }
    $items[] = [
        'number' => sprintf('#%03d', $chart->id),
        'title' => format_string($chart->title),
        'meta' => page::clean_symbol($chart->symbol) . ' · ' . page::timeframe_label($chart->timeframe),
        'url' => (new moodle_url('/local/alphatrade/chart.php', ['id' => $chart->id]))->out(false),
        'editurl' => $canmanage ? (new moodle_url('/local/alphatrade/chartedit.php', ['id' => $chart->id]))->out(false) : '',
        'hidden' => !$chart->visible,
        'status' => $status,
    ];
}

$data = [
    'backurl' => (new moodle_url('/local/alphatrade/practice.php'))->out(false),
    'items' => $items,
    'hasitems' => !empty($items),
    'canmanage' => $canmanage,
    'newurl' => (new moodle_url('/local/alphatrade/chartedit.php'))->out(false),
    'reviewsurl' => page::is_staff('local/alphatrade:reviewanalysis')
        ? (new moodle_url('/local/alphatrade/reviews.php'))->out(false) : '',
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/charts', $data);
echo $OUTPUT->footer();
