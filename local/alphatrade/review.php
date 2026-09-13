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
 * Teacher: correct one chart analysis ("Correction Alpha Trade - Analyse du formateur").
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\review_form;
use local_alphatrade\local\analysis;
use local_alphatrade\local\page;

$id = required_param('id', PARAM_INT);

page::setup('/local/alphatrade/review.php', 'reviews', get_string('correctanalysis', 'local_alphatrade'), ['id' => $id],
    get_string('sub_review', 'local_alphatrade'));
$context = page::programme_context();
require_capability('local/alphatrade:reviewanalysis', $context);

$submission = $DB->get_record('local_alphatrade_analysis', ['id' => $id], '*', MUST_EXIST);
$chart = $DB->get_record('local_alphatrade_chart', ['id' => $submission->chartid], '*', MUST_EXIST);
$student = core_user::get_user($submission->userid, '*', MUST_EXIST);
$listurl = new moodle_url('/local/alphatrade/reviews.php');

$form = new review_form($PAGE->url);
$defaults = ['id' => $submission->id, 'score' => $submission->score, 'feedback' => $submission->feedback];
foreach (analysis::CRITERIA as $criterion) {
    if ($submission->$criterion !== null) {
        $defaults[$criterion] = $submission->$criterion;
    }
}
$form->set_data($defaults);

if ($form->is_cancelled()) {
    redirect($listurl);
} else if ($formdata = $form->get_data()) {
    $record = (object) [
        'id' => $submission->id,
        'status' => 'reviewed',
        'score' => max(0, min(100, (int) $formdata->score)),
        'feedback' => $formdata->feedback,
        'reviewerid' => $USER->id,
        'timereviewed' => time(),
    ];
    foreach (analysis::CRITERIA as $criterion) {
        $record->$criterion = max(0, min(2, (int) $formdata->$criterion));
    }
    $DB->update_record('local_alphatrade_analysis', $record);

    $charturl = new moodle_url('/local/alphatrade/chart.php', ['id' => $chart->id]);
    $message = new \core\message\message();
    $message->component = 'local_alphatrade';
    $message->name = 'analysisreviewed';
    $message->userfrom = core_user::get_noreply_user();
    $message->userto = $student;
    $message->subject = get_string('notification_reviewed_subject', 'local_alphatrade', format_string($chart->title));
    $message->fullmessage = get_string('notification_reviewed_body', 'local_alphatrade',
        ['chart' => format_string($chart->title), 'score' => $record->score, 'url' => $charturl->out(false)]);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '';
    $message->smallmessage = $message->subject;
    $message->notification = 1;
    $message->contexturl = $charturl->out(false);
    $message->contexturlname = format_string($chart->title);
    message_send($message);

    redirect($listurl, get_string('reviewsaved', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$answers = analysis::export_submission($submission, $chart, $context);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/review', [
    'backurl' => $listurl->out(false),
    'student' => fullname($student),
    'number' => sprintf('#%03d', $chart->id),
    'title' => format_string($chart->title),
    'symbol' => page::display_symbol($chart->symbol),
    'timeframe' => page::timeframe_label($chart->timeframe),
    'chartsrc' => page::tradingview_url($chart->symbol, $chart->timeframe),
    'answers' => $answers,
    'form' => $form->render(),
]);
echo $OUTPUT->footer();
