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
 * Strategy dashboard (wireframe v3 screen 9): stats, equity curve or candles, trades, add a trade.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\strategy_form;
use local_alphatrade\form\trade_form;
use local_alphatrade\local\backtest;
use local_alphatrade\local\page;
use local_alphatrade\local\stats;

$id = required_param('id', PARAM_INT);
$view = optional_param('view', 'curve', PARAM_ALPHA);
$view = in_array($view, ['curve', 'candles']) ? $view : 'curve';
$action = optional_param('action', '', PARAM_ALPHA);

page::setup('/local/alphatrade/strategy.php', 'backtest', get_string('backtestinglab', 'local_alphatrade'), ['id' => $id]);

$strategy = $DB->get_record('local_alphatrade_strategy', ['id' => $id], '*', MUST_EXIST);
if (!backtest::can_view($strategy)) {
    throw new required_capability_exception(page::programme_context(), 'local/alphatrade:viewstudentdata', 'nopermissions', '');
}
$canedit = $strategy->userid == $USER->id;
$pageurl = new moodle_url('/local/alphatrade/strategy.php', ['id' => $strategy->id]);
$listurl = new moodle_url('/local/alphatrade/backtesting.php');

// Owner actions.
if ($canedit && $action === 'deletetrade') {
    require_sesskey();
    $tradeid = required_param('tradeid', PARAM_INT);
    $DB->delete_records('local_alphatrade_bttrade', ['id' => $tradeid, 'strategyid' => $strategy->id]);
    $DB->set_field('local_alphatrade_strategy', 'timemodified', time(), ['id' => $strategy->id]);
    redirect(new moodle_url($pageurl, [], 'trades'), get_string('deleted', 'local_alphatrade'));
}

if ($canedit && $action === 'delete') {
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        $DB->delete_records('local_alphatrade_bttrade', ['strategyid' => $strategy->id]);
        $DB->delete_records('local_alphatrade_strategy', ['id' => $strategy->id]);
        redirect($listurl, get_string('deleted', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('confirmdeletestrategy', 'local_alphatrade', format_string($strategy->name)),
        new moodle_url($pageurl, ['action' => 'delete', 'confirm' => 1, 'sesskey' => sesskey()]), $pageurl);
    echo $OUTPUT->footer();
    exit;
}

if ($canedit && $action === 'edit') {
    $form = new strategy_form(new moodle_url($pageurl, ['action' => 'edit']));
    $form->set_data($strategy);
    if ($form->is_cancelled()) {
        redirect($pageurl);
    } else if ($formdata = $form->get_data()) {
        $DB->update_record('local_alphatrade_strategy', (object) [
            'id' => $strategy->id,
            'name' => $formdata->name,
            'market' => page::clean_symbol($formdata->market),
            'timeframe' => $formdata->timeframe,
            'period' => $formdata->period,
            'description' => $formdata->description,
            'timemodified' => time(),
        ]);
        redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/formpage', [
        'backurl' => $pageurl->out(false),
        'backlabel' => format_string($strategy->name),
        'eyebrow' => get_string('backtestinglab', 'local_alphatrade'),
        'title' => get_string('editstrategy', 'local_alphatrade'),
        'lead' => '',
        'form' => $form->render(),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$tradeform = null;
if ($canedit && $action === 'addtrade') {
    $tradeform = new trade_form(new moodle_url($pageurl, ['action' => 'addtrade']));
    $tradeform->set_data(['id' => $strategy->id, 'tradedate' => time()]);
    if ($tradeform->is_cancelled()) {
        redirect($pageurl);
    } else if ($formdata = $tradeform->get_data()) {
        $DB->insert_record('local_alphatrade_bttrade', (object) [
            'strategyid' => $strategy->id,
            'userid' => $USER->id,
            'tradedate' => $formdata->tradedate,
            'direction' => $formdata->direction === 'short' ? 'short' : 'long',
            'entry' => trade_form::to_float($formdata->entry),
            'stoploss' => trade_form::to_float($formdata->stoploss),
            'takeprofit' => trade_form::to_float($formdata->takeprofit),
            'resultr' => trade_form::to_float($formdata->resultr),
            'notes' => $formdata->notes,
            'timecreated' => time(),
        ]);
        $DB->set_field('local_alphatrade_strategy', 'timemodified', time(), ['id' => $strategy->id]);
        // Stay on the form: trades are usually entered in series.
        redirect(new moodle_url($pageurl, ['action' => 'addtrade']), get_string('tradesaved', 'local_alphatrade'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Dashboard.
$trades = $DB->get_records('local_alphatrade_bttrade', ['strategyid' => $strategy->id], 'tradedate ASC, id ASC');
$results = array_map(function($trade) {
    return (float) $trade->resultr;
}, array_values($trades));
$computed = stats::compute($results);

$decimal = function($value): string {
    return $value === null ? '-' : format_float((float) $value, 5, true, true);
};
$rows = [];
$number = 0;
foreach ($trades as $trade) {
    $number++;
    $rows[] = [
        'number' => $number,
        'date' => userdate($trade->tradedate, get_string('strftimedatefullshort', 'langconfig')),
        'direction' => get_string('direction_' . $trade->direction, 'local_alphatrade'),
        'islong' => $trade->direction === 'long',
        'entry' => $decimal($trade->entry),
        'stoploss' => $decimal($trade->stoploss),
        'takeprofit' => $decimal($trade->takeprofit),
        'result' => page::format_r((float) $trade->resultr),
        'resultclass' => page::value_class((float) $trade->resultr),
        'notes' => (string) $trade->notes,
        'deleteurl' => $canedit ? (new moodle_url($pageurl, ['action' => 'deletetrade', 'tradeid' => $trade->id,
            'sesskey' => sesskey()]))->out(false) : '',
    ];
}
$rows = array_reverse($rows);

$owner = $canedit ? null : core_user::get_user($strategy->userid);

$data = [
    'backurl' => $canedit ? $listurl->out(false)
        : (new moodle_url('/local/alphatrade/teacher.php', ['view' => 'backtests']))->out(false),
    'name' => format_string($strategy->name),
    'market' => page::clean_symbol($strategy->market),
    'timeframe' => page::timeframe_label($strategy->timeframe),
    'period' => format_string((string) $strategy->period),
    'rules' => nl2br(s((string) $strategy->description)),
    'hasrules' => trim((string) $strategy->description) !== '',
    'owner' => $owner ? fullname($owner) : '',
    'canedit' => $canedit,
    'stats' => stats::export($computed),
    'iscurve' => $view === 'curve',
    'curveurl' => (new moodle_url($pageurl, ['view' => 'curve']))->out(false),
    'candlesurl' => (new moodle_url($pageurl, ['view' => 'candles']))->out(false),
    'equity' => stats::equity_svg($computed['equity']),
    'chartsrc' => page::tradingview_url($strategy->market, $strategy->timeframe),
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'addtradeurl' => (new moodle_url($pageurl, ['action' => 'addtrade'], 'addtrade'))->out(false),
    'editurl' => (new moodle_url($pageurl, ['action' => 'edit']))->out(false),
    'deleteurl' => (new moodle_url($pageurl, ['action' => 'delete']))->out(false),
    'tradeform' => $tradeform ? $tradeform->render() : '',
    'minsample' => get_string('minsample', 'local_alphatrade', stats::MIN_SAMPLE),
];
$data['stats']['verdictsuccess'] = $data['stats']['verdictlevel'] === 'success';
$data['stats']['verdictwarning'] = $data['stats']['verdictlevel'] === 'warning';

$PAGE->set_title(format_string($strategy->name));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/strategy', $data);
echo $OUTPUT->footer();
