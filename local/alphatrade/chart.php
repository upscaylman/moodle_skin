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
 * Chart analysis (wireframe v3 screen 7): chart, structured analysis form, trainer correction.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\analysis;
use local_alphatrade\local\page;

$id = required_param('id', PARAM_INT);
$edit = optional_param('edit', 0, PARAM_BOOL);

page::setup('/local/alphatrade/chart.php', 'practice', get_string('practice_charts', 'local_alphatrade'), ['id' => $id]);

$chart = $DB->get_record('local_alphatrade_chart', ['id' => $id], '*', MUST_EXIST);
if (!$chart->visible && !page::is_staff('local/alphatrade:managecharts')) {
    throw new moodle_exception('chartnotavailable', 'local_alphatrade');
}

$submission = $DB->get_record('local_alphatrade_analysis', ['chartid' => $chart->id, 'userid' => $USER->id]);
$editable = !$submission || $submission->status !== 'reviewed';
$pageurl = new moodle_url('/local/alphatrade/chart.php', ['id' => $chart->id]);

if ($editable && data_submitted()) {
    require_sesskey();
    $bias = optional_param('bias', '', PARAM_ALPHA);
    $record = (object) [
        'structure' => trim(optional_param('structure', '', PARAM_TEXT)),
        'liquidity' => trim(optional_param('liquidity', '', PARAM_TEXT)),
        'bias' => in_array($bias, analysis::BIASES) ? $bias : null,
        'scenario' => trim(optional_param('scenario', '', PARAM_TEXT)),
        'timemodified' => time(),
    ];
    if ($record->structure === '' || $record->scenario === '' || !$record->bias) {
        redirect(new moodle_url($pageurl, ['edit' => 1]), get_string('analysisincomplete', 'local_alphatrade'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    if ($submission) {
        $record->id = $submission->id;
        $DB->update_record('local_alphatrade_analysis', $record);
    } else {
        $record->chartid = $chart->id;
        $record->userid = $USER->id;
        $record->status = 'submitted';
        $record->timecreated = time();
        $DB->insert_record('local_alphatrade_analysis', $record);
    }
    redirect($pageurl, get_string('analysissaved', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$context = page::programme_context();
$data = [
    'backurl' => (new moodle_url('/local/alphatrade/charts.php'))->out(false),
    'number' => sprintf('#%03d', $chart->id),
    'title' => format_string($chart->title),
    'symbol' => page::clean_symbol($chart->symbol),
    'timeframe' => page::timeframe_label($chart->timeframe),
    'chartsrc' => page::tradingview_url($chart->symbol, $chart->timeframe),
    'instructions' => format_text($chart->instructions, $chart->instructionsformat, ['context' => $context]),
    'showform' => $editable && (!$submission || $edit),
    'actionurl' => $pageurl->out(false),
    'sesskey' => sesskey(),
    'biases' => analysis::bias_options($submission->bias ?? ''),
    'structure' => $submission->structure ?? '',
    'liquidity' => $submission->liquidity ?? '',
    'scenario' => $submission->scenario ?? '',
];

if ($submission && !$data['showform']) {
    $data['submission'] = analysis::export_submission($submission, $chart, $context);
    $data['editurl'] = $editable ? (new moodle_url($pageurl, ['edit' => 1]))->out(false) : '';
}

$PAGE->set_title(get_string('analysisnumber', 'local_alphatrade', $data['number']));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/chart', $data);
echo $OUTPUT->footer();
