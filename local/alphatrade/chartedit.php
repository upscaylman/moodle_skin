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
 * Teacher: create / edit / delete a chart analysis exercise.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\chart_form;
use local_alphatrade\local\page;

$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);

page::setup('/local/alphatrade/chartedit.php', 'practice', get_string('newchart', 'local_alphatrade'), $id ? ['id' => $id] : []);
require_capability('local/alphatrade:managecharts', page::programme_context());

$listurl = new moodle_url('/local/alphatrade/charts.php');
$chart = $id ? $DB->get_record('local_alphatrade_chart', ['id' => $id], '*', MUST_EXIST) : null;

if ($chart && $delete) {
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        $DB->delete_records('local_alphatrade_analysis', ['chartid' => $chart->id]);
        $DB->delete_records('local_alphatrade_chart', ['id' => $chart->id]);
        redirect($listurl, get_string('deleted', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdeletechart', 'local_alphatrade', format_string($chart->title)),
        new moodle_url($PAGE->url, ['delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $listurl
    );
    echo $OUTPUT->footer();
    exit;
}

$form = new chart_form($PAGE->url);
if ($chart) {
    $defaults = clone($chart);
    $defaults->instructions_editor = ['text' => $chart->instructions, 'format' => $chart->instructionsformat];
    $defaults->correction_editor = ['text' => $chart->correction, 'format' => $chart->correctionformat];
    $form->set_data($defaults);
}

if ($form->is_cancelled()) {
    redirect($listurl);
} else if ($formdata = $form->get_data()) {
    $record = (object) [
        'title' => $formdata->title,
        'symbol' => page::clean_symbol($formdata->symbol),
        'timeframe' => $formdata->timeframe,
        'instructions' => $formdata->instructions_editor['text'],
        'instructionsformat' => $formdata->instructions_editor['format'],
        'correction' => $formdata->correction_editor['text'],
        'correctionformat' => $formdata->correction_editor['format'],
        'sortorder' => (int) $formdata->sortorder,
        'visible' => (int) $formdata->visible,
        'usermodified' => $USER->id,
        'timemodified' => time(),
    ];
    if ($chart) {
        $record->id = $chart->id;
        $DB->update_record('local_alphatrade_chart', $record);
    } else {
        $record->timecreated = time();
        $DB->insert_record('local_alphatrade_chart', $record);
    }
    redirect($listurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo html_writer::start_div('alpha-formpage');
echo html_writer::link($listurl, html_writer::tag('i', '', ['class' => 'ph ph-arrow-left', 'aria-hidden' => 'true']) .
    html_writer::span(get_string('practice_charts', 'local_alphatrade')), ['class' => 'alpha-backlink']);
echo html_writer::start_tag('header', ['class' => 'alpha-pagehead']);
echo html_writer::start_div();
echo html_writer::tag('h1', $chart ? format_string($chart->title) : get_string('newchart', 'local_alphatrade'), ['class' => 'alpha-title']);
echo html_writer::end_div();
if ($chart) {
    echo html_writer::link(new moodle_url($PAGE->url, ['delete' => 1]),
        html_writer::tag('i', '', ['class' => 'ph ph-trash', 'aria-hidden' => 'true']) . get_string('delete'),
        ['class' => 'btn btn-secondary']);
}
echo html_writer::end_tag('header');
echo html_writer::start_div('alpha-card');
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
