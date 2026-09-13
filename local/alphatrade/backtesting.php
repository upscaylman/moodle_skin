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
 * Backtesting Lab (maquette Portail Etudiant, screen "Backtest"): my strategies, create / edit a strategy,
 * verify all trades against real market data.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\backtest;
use local_alphatrade\local\marketdata;
use local_alphatrade\local\markets;
use local_alphatrade\local\page;
use local_alphatrade\local\verifier;

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

page::setup('/local/alphatrade/backtesting.php', 'backtest', get_string('backtesting', 'local_alphatrade'),
    array_filter(['action' => $action, 'id' => $id]), get_string('sub_backtest', 'local_alphatrade'));
$listurl = new moodle_url('/local/alphatrade/backtesting.php');

$editing = $id ? $DB->get_record('local_alphatrade_strategy', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST) : null;

// Create or update a strategy.
if (($action === 'save') && data_submitted()) {
    require_sesskey();
    $name = core_text::substr(trim(required_param('name', PARAM_TEXT)), 0, 255);
    $market = required_param('market', PARAM_TEXT);
    $timeframe = required_param('timeframe', PARAM_ALPHANUM);
    $preset = markets::find($market);
    if (!$preset || !isset(markets::TIMEFRAMES[$timeframe]) && !isset(page::timeframes()[$timeframe])) {
        throw new moodle_exception('invalidsymbol', 'local_alphatrade');
    }
    $count = $DB->count_records('local_alphatrade_strategy', ['userid' => $USER->id]);
    $record = (object) [
        'name' => $name !== '' ? $name : get_string('strategydefaultname', 'local_alphatrade', sprintf('%02d', $count + 1)),
        'market' => $preset['label'],
        'timeframe' => $timeframe,
        'period' => core_text::substr(trim(optional_param('period', '', PARAM_TEXT)), 0, 64),
        'description' => trim(optional_param('description', '', PARAM_TEXT)),
        'timemodified' => time(),
    ];
    if ($editing) {
        $record->id = $editing->id;
        $DB->update_record('local_alphatrade_strategy', $record);
        $newid = $editing->id;
    } else {
        $record->userid = $USER->id;
        $record->timecreated = time();
        $newid = $DB->insert_record('local_alphatrade_strategy', $record);
    }
    redirect(new moodle_url('/local/alphatrade/strategy.php', ['id' => $newid]));
}

// Verify every strategy against real market data.
if ($action === 'verifyall' && data_submitted()) {
    require_sesskey();
    $totals = ['confirmed' => 0, 'mismatch' => 0, 'open' => 0, 'nodata' => 0];
    try {
        foreach ($DB->get_records('local_alphatrade_strategy', ['userid' => $USER->id]) as $strategy) {
            foreach (verifier::verify_strategy($strategy) as $status => $count) {
                $totals[$status] += $count;
            }
        }
        redirect($listurl, get_string('verifydone', 'local_alphatrade', (object) $totals), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {
        redirect($listurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

$strategies = $DB->get_records('local_alphatrade_strategy', ['userid' => $USER->id], 'timecreated ASC');
$results = backtest::get_results_for(array_keys($strategies));
$cards = [];
foreach ($strategies as $strategy) {
    $cards[] = backtest::export_card($strategy, $results[$strategy->id]);
}

$showform = $action === 'new' || $editing;
$currentmarket = $editing ? markets::label($editing->market) : (markets::all()[0]['label'] ?? '');
$currenttf = $editing ? $editing->timeframe : '60';
$marketoptions = array_map(function($preset) use ($currentmarket) {
    return ['value' => $preset['label'], 'label' => $preset['label'], 'checked' => $preset['label'] === $currentmarket];
}, markets::all());
$tfoptions = [];
foreach (markets::TIMEFRAMES as $value => $label) {
    $tfoptions[] = ['value' => $value, 'label' => $label, 'checked' => (string) $value === (string) $currenttf];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/backtesting', [
    'cards' => $cards,
    'hascards' => !empty($cards),
    'newurl' => (new moodle_url($listurl, ['action' => 'new']))->out(false),
    'listurl' => $listurl->out(false),
    'showform' => $showform,
    'editing' => (bool) $editing,
    'formaction' => (new moodle_url($listurl, array_filter(['action' => 'save', 'id' => $editing->id ?? 0])))->out(false),
    'sesskey' => sesskey(),
    'name' => $editing->name ?? '',
    'period' => $editing->period ?? '',
    'description' => $editing->description ?? '',
    'markets' => $marketoptions,
    'timeframes' => $tfoptions,
    'canverify' => marketdata::is_configured() && !empty($cards),
    'verifyurl' => (new moodle_url($listurl, ['action' => 'verifyall']))->out(false),
]);
echo $OUTPUT->footer();
