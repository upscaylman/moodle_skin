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
 * Trade sheet: view, add, edit, delete a journal entry.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\journal_form;
use local_alphatrade\form\trade_form;
use local_alphatrade\local\journal;
use local_alphatrade\local\page;

$id = optional_param('id', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_BOOL);
$delete = optional_param('delete', 0, PARAM_BOOL);

$urlparams = array_filter(['id' => $id, 'edit' => $edit ? 1 : 0]);
page::setup('/local/alphatrade/journalentry.php', 'journal', get_string('tradesheet', 'local_alphatrade'), $urlparams,
    get_string('sub_trade', 'local_alphatrade'));

$listurl = new moodle_url('/local/alphatrade/journal.php');
$entry = $id ? $DB->get_record('local_alphatrade_journal', ['id' => $id], '*', MUST_EXIST) : null;
if ($entry && !journal::can_view($entry)) {
    throw new required_capability_exception(page::programme_context(), 'local/alphatrade:viewstudentdata', 'nopermissions', '');
}
$canedit = !$entry || $entry->userid == $USER->id;
$usercontext = context_user::instance($entry ? $entry->userid : $USER->id);

if ($entry && $canedit && $delete) {
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        get_file_storage()->delete_area_files($usercontext->id, 'local_alphatrade', journal::FILEAREA, $entry->id);
        $DB->delete_records('local_alphatrade_journal', ['id' => $entry->id]);
        redirect($listurl, get_string('deleted', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('confirmdeletetrade', 'local_alphatrade'),
        new moodle_url('/local/alphatrade/journalentry.php', ['id' => $entry->id, 'delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        new moodle_url('/local/alphatrade/journalentry.php', ['id' => $entry->id]));
    echo $OUTPUT->footer();
    exit;
}

if ($canedit && ($edit || !$entry)) {
    $form = new journal_form(new moodle_url('/local/alphatrade/journalentry.php', $urlparams));
    $options = journal_form::filemanager_options();
    $draftitemid = file_get_submitted_draft_itemid('screenshot_filemanager');
    file_prepare_draft_area($draftitemid, $usercontext->id, 'local_alphatrade', journal::FILEAREA, $entry->id ?? 0, $options);

    $defaults = $entry ? clone($entry) : (object) ['tradedate' => time(), 'assetclass' => 'forex', 'direction' => 'long'];
    $defaults->edit = 1;
    $defaults->screenshot_filemanager = $draftitemid;
    if ($entry) {
        $defaults->followedplan = $entry->followedplan === null ? '' : (string) $entry->followedplan;
        foreach (['entry', 'stoploss', 'takeprofit', 'riskpct', 'resultr'] as $field) {
            $defaults->$field = $entry->$field === null ? '' : format_float((float) $entry->$field, 5, true, true);
        }
    }
    $form->set_data($defaults);

    $cancelurl = $entry ? new moodle_url('/local/alphatrade/journalentry.php', ['id' => $entry->id]) : $listurl;
    if ($form->is_cancelled()) {
        redirect($cancelurl);
    } else if ($formdata = $form->get_data()) {
        $record = (object) [
            'tradedate' => $formdata->tradedate,
            'asset' => core_text::strtoupper(trim($formdata->asset)),
            'assetclass' => in_array($formdata->assetclass, journal::ASSETCLASSES) ? $formdata->assetclass : 'other',
            'direction' => $formdata->direction === 'short' ? 'short' : 'long',
            'setup' => $formdata->setup,
            'entry' => trade_form::to_float($formdata->entry),
            'stoploss' => trade_form::to_float($formdata->stoploss),
            'takeprofit' => trade_form::to_float($formdata->takeprofit),
            'riskpct' => trade_form::to_float($formdata->riskpct),
            'resultr' => trade_form::to_float($formdata->resultr),
            'emotion' => isset(journal::EMOTIONS[$formdata->emotion]) ? $formdata->emotion : null,
            'reason' => $formdata->reason,
            'marketcontext' => $formdata->marketcontext,
            'followedplan' => $formdata->followedplan === '' ? null : (int) $formdata->followedplan,
            'review' => $formdata->review,
            'timemodified' => time(),
        ];
        if ($entry) {
            $record->id = $entry->id;
            $DB->update_record('local_alphatrade_journal', $record);
        } else {
            $record->userid = $USER->id;
            $record->timecreated = time();
            $record->id = $DB->insert_record('local_alphatrade_journal', $record);
        }
        file_save_draft_area_files($formdata->screenshot_filemanager, $usercontext->id, 'local_alphatrade',
            journal::FILEAREA, $record->id, $options);
        redirect(new moodle_url('/local/alphatrade/journalentry.php', ['id' => $record->id]),
            get_string('tradesaved', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/formpage', [
        'backurl' => $cancelurl->out(false),
        'backlabel' => $entry ? s($entry->asset) : get_string('journal', 'local_alphatrade'),
        'eyebrow' => '',
        'title' => $entry ? get_string('edittrade', 'local_alphatrade') : get_string('addtrade', 'local_alphatrade'),
        'lead' => get_string('journal_form_lead', 'local_alphatrade'),
        'form' => $form->render(),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$sheeturl = new moodle_url('/local/alphatrade/journalentry.php', ['id' => $entry->id]);

// Inline save of the "before / after the trade" part of the sheet.
if ($canedit && optional_param('action', '', PARAM_ALPHA) === 'savesheet' && data_submitted()) {
    require_sesskey();
    $plan = optional_param('followedplan', '', PARAM_RAW_TRIMMED);
    $emotion = optional_param('emotion', '', PARAM_ALPHA);
    $DB->update_record('local_alphatrade_journal', (object) [
        'id' => $entry->id,
        'reason' => core_text::substr(optional_param('reason', '', PARAM_TEXT), 0, 2000),
        'marketcontext' => core_text::substr(optional_param('marketcontext', '', PARAM_TEXT), 0, 2000),
        'review' => core_text::substr(optional_param('review', '', PARAM_TEXT), 0, 2000),
        'followedplan' => $plan === '1' ? 1 : ($plan === '0' ? 0 : null),
        'emotion' => isset(journal::EMOTIONS[$emotion]) ? $emotion : null,
        'timemodified' => time(),
    ]);
    redirect($sheeturl, get_string('tradesaved', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Trade sheet.
$decimal = function($value, int $decimals = 5): string {
    return $value === null ? '-' : format_float((float) $value, $decimals, true, true);
};
$result = $entry->resultr === null ? null : (float) $entry->resultr;
$owner = $entry->userid == $USER->id ? null : core_user::get_user($entry->userid);

$emotions = [];
foreach (journal::EMOTIONS as $key => $icon) {
    $emotions[] = [
        'value' => $key,
        'icon' => $icon,
        'label' => get_string('emotion_' . $key, 'local_alphatrade'),
        'checked' => $entry->emotion === $key,
    ];
}

$data = array_merge(journal::export_row($entry), [
    'backurl' => (new moodle_url('/local/alphatrade/journal.php', $owner ? ['userid' => $entry->userid] : []))->out(false),
    'title' => page::display_symbol($entry->asset) . ' - '
        . core_text::strtoupper(get_string('direction_' . $entry->direction . '_short', 'local_alphatrade')),
    'meta' => implode(' · ', array_filter([
        userdate($entry->tradedate, '%d/%m/%Y'),
        $entry->setup ? get_string('setup', 'local_alphatrade') . ' ' . $entry->setup : '',
        $owner ? fullname($owner) : '',
    ])),
    'entry' => $decimal($entry->entry),
    'stoploss' => $decimal($entry->stoploss),
    'takeprofit' => $decimal($entry->takeprofit),
    'riskpct' => $entry->riskpct === null ? '-' : $decimal($entry->riskpct, 2) . ' %',
    'reason' => (string) $entry->reason,
    'marketcontext' => (string) $entry->marketcontext,
    'review' => (string) $entry->review,
    'hasfollowedplan' => $entry->followedplan !== null,
    'followedplan' => (bool) $entry->followedplan,
    'planyes' => $entry->followedplan !== null && (int) $entry->followedplan === 1,
    'planno' => $entry->followedplan !== null && (int) $entry->followedplan === 0,
    'emotions' => $emotions,
    'screenshots' => journal::get_screenshots($entry),
    'canedit' => $canedit,
    'saveurl' => (new moodle_url($sheeturl, ['action' => 'savesheet']))->out(false),
    'sesskey' => sesskey(),
    'editurl' => (new moodle_url($sheeturl, ['edit' => 1]))->out(false),
    'deleteurl' => (new moodle_url($sheeturl, ['delete' => 1]))->out(false),
]);
$data['result'] = page::format_r($result);
$data['hasscreenshots'] = !empty($data['screenshots']);

$PAGE->set_title($data['title']);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/journalentry', $data);
echo $OUTPUT->footer();
