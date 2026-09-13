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
 * Strategy detail (maquette Portail Etudiant, screen "Stratégie"): stats, curve or real market candles,
 * statistics, trades, add a trade, verification against real market data.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\trade_form;
use local_alphatrade\local\backtest;
use local_alphatrade\local\candles;
use local_alphatrade\local\marketdata;
use local_alphatrade\local\markets;
use local_alphatrade\local\page;
use local_alphatrade\local\stats;
use local_alphatrade\local\verifier;

$id = required_param('id', PARAM_INT);
$view = optional_param('view', 'curve', PARAM_ALPHA);
$view = $view === 'candles' ? 'candles' : 'curve';
$action = optional_param('action', '', PARAM_ALPHA);

page::setup('/local/alphatrade/strategy.php', 'backtest', get_string('strategy', 'local_alphatrade'), ['id' => $id],
    get_string('sub_strategy', 'local_alphatrade'));

$strategy = $DB->get_record('local_alphatrade_strategy', ['id' => $id], '*', MUST_EXIST);
if (!backtest::can_view($strategy)) {
    throw new required_capability_exception(page::programme_context(), 'local/alphatrade:viewstudentdata', 'nopermissions', '');
}
$canedit = $strategy->userid == $USER->id;
$pageurl = new moodle_url('/local/alphatrade/strategy.php', ['id' => $strategy->id]);
$listurl = new moodle_url('/local/alphatrade/backtesting.php');

if ($canedit && $action === 'savetrade' && data_submitted()) {
    require_sesskey();
    $result = trade_form::to_float(optional_param('resultr', '', PARAM_RAW_TRIMMED));
    $datestring = optional_param('tradedate', '', PARAM_RAW_TRIMMED);
    $tradedate = $datestring !== '' ? strtotime($datestring . ' 12:00:00') : time();
    if ($result === null || abs($result) > 100 || !$tradedate) {
        redirect(new moodle_url($pageurl, ['action' => 'addtrade'], 'addtrade'), get_string('invalidresultr', 'local_alphatrade'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    $DB->insert_record('local_alphatrade_bttrade', (object) [
        'strategyid' => $strategy->id,
        'userid' => $USER->id,
        'tradedate' => $tradedate,
        'direction' => optional_param('direction', 'long', PARAM_ALPHA) === 'short' ? 'short' : 'long',
        'entry' => trade_form::to_float(optional_param('entry', '', PARAM_RAW_TRIMMED)),
        'stoploss' => trade_form::to_float(optional_param('stoploss', '', PARAM_RAW_TRIMMED)),
        'takeprofit' => trade_form::to_float(optional_param('takeprofit', '', PARAM_RAW_TRIMMED)),
        'resultr' => $result,
        'notes' => null,
        'timecreated' => time(),
    ]);
    $DB->set_field('local_alphatrade_strategy', 'timemodified', time(), ['id' => $strategy->id]);
    redirect(new moodle_url($pageurl, ['action' => 'addtrade'], 'addtrade'), get_string('tradesaved', 'local_alphatrade'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($canedit && $action === 'deletetrade') {
    require_sesskey();
    $DB->delete_records('local_alphatrade_bttrade', ['id' => required_param('tradeid', PARAM_INT), 'strategyid' => $strategy->id]);
    redirect(new moodle_url($pageurl, [], 'trades'));
}

if ($canedit && $action === 'verify' && data_submitted()) {
    require_sesskey();
    try {
        $counts = verifier::verify_strategy($strategy);
        redirect(new moodle_url($pageurl, [], 'trades'), get_string('verifydone', 'local_alphatrade', (object) $counts), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {
        redirect($pageurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
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

$trades = $DB->get_records('local_alphatrade_bttrade', ['strategyid' => $strategy->id], 'tradedate ASC, id ASC');
$computed = stats::compute(array_map(function($trade) {
    return (float) $trade->resultr;
}, array_values($trades)));

$rows = [];
$verifyicons = [
    'confirmed' => ['ph-fill ph-check-circle', 'at-status-done'],
    'mismatch' => ['ph ph-warning', 'at-icon-warning'],
    'open' => ['ph ph-clock', 'at-status-locked'],
    'nodata' => ['ph ph-minus-circle', 'at-status-locked'],
];
foreach (array_reverse($trades) as $trade) {
    $status = $trade->verifystatus ?? '';
    $rows[] = [
        'date' => userdate($trade->tradedate, '%d/%m/%Y'),
        'direction' => get_string('direction_' . $trade->direction . '_short', 'local_alphatrade'),
        'result' => page::format_r((float) $trade->resultr, 2, false),
        'resultclass' => page::value_class((float) $trade->resultr),
        'hasverify' => isset($verifyicons[$status]),
        'verifyicon' => $verifyicons[$status][0] ?? '',
        'verifyclass' => $verifyicons[$status][1] ?? '',
        'verifylabel' => $status ? get_string('verify_' . $status, 'local_alphatrade',
            $trade->verifiedr === null ? '-' : page::format_r((float) $trade->verifiedr)) : '',
        'deleteurl' => $canedit ? (new moodle_url($pageurl, ['action' => 'deletetrade', 'tradeid' => $trade->id,
            'sesskey' => sesskey()]))->out(false) : '',
    ];
}

$market = markets::label($strategy->market);
$candledata = ['hascandles' => false];
$candleerror = '';
if ($view === 'candles' && marketdata::is_configured() && ($preset = markets::find($strategy->market)) && $preset['lse'] !== '') {
    try {
        $candledata = candles::svg(array_reverse(marketdata::candles($preset['lse'], $strategy->timeframe, null, null, 120, 'desc')));
    } catch (moodle_exception $e) {
        $candleerror = $e->getMessage();
    }
}

$owner = $canedit ? null : core_user::get_user($strategy->userid);
$exported = stats::export($computed);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/strategy', [
    'backurl' => $canedit ? $listurl->out(false) : (new moodle_url('/local/alphatrade/teacher.php', ['view' => 'backtests']))->out(false),
    'name' => format_string($strategy->name),
    'meta' => $market . ' · ' . page::timeframe_label($strategy->timeframe) . ($owner ? ' · ' . fullname($owner) : ''),
    'stats' => $exported,
    'iscurve' => $view === 'curve',
    'curveurl' => (new moodle_url($pageurl, ['view' => 'curve']))->out(false),
    'candlesurl' => (new moodle_url($pageurl, ['view' => 'candles']))->out(false),
    'polyline' => stats::equity_polyline($computed['equity']),
    'candles' => $candledata,
    'userealdata' => $candledata['hascandles'],
    'candleerror' => $candleerror,
    'chartsrc' => page::tradingview_url(markets::tv_symbol($strategy->market), $strategy->timeframe),
    'canedit' => $canedit,
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'adding' => $action === 'addtrade',
    'addurl' => (new moodle_url($pageurl, ['action' => 'addtrade'], 'addtrade'))->out(false),
    'saveurl' => (new moodle_url($pageurl, ['action' => 'savetrade']))->out(false),
    'today' => date('Y-m-d'),
    'sesskey' => sesskey(),
    'canverify' => $canedit && marketdata::is_configured() && !empty($rows),
    'verifyurl' => (new moodle_url($pageurl, ['action' => 'verify']))->out(false),
    'editurl' => (new moodle_url($listurl, ['id' => $strategy->id]))->out(false),
    'deleteurl' => (new moodle_url($pageurl, ['action' => 'delete']))->out(false),
]);
echo $OUTPUT->footer();
