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
 * Backtesting Lab: my strategies (wireframe v3 screen 8) and "new strategy".
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\strategy_form;
use local_alphatrade\local\backtest;
use local_alphatrade\local\page;

$action = optional_param('action', '', PARAM_ALPHA);

page::setup('/local/alphatrade/backtesting.php', 'backtest', get_string('backtestinglab', 'local_alphatrade'),
    $action === 'new' ? ['action' => 'new'] : []);
$listurl = new moodle_url('/local/alphatrade/backtesting.php');

if ($action === 'new') {
    $form = new strategy_form($PAGE->url);
    if ($form->is_cancelled()) {
        redirect($listurl);
    } else if ($formdata = $form->get_data()) {
        $id = $DB->insert_record('local_alphatrade_strategy', (object) [
            'userid' => $USER->id,
            'name' => $formdata->name,
            'market' => page::clean_symbol($formdata->market),
            'timeframe' => $formdata->timeframe,
            'period' => $formdata->period,
            'description' => $formdata->description,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        redirect(new moodle_url('/local/alphatrade/strategy.php', ['id' => $id]),
            get_string('strategycreated', 'local_alphatrade'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/formpage', [
        'backurl' => $listurl->out(false),
        'backlabel' => get_string('backtestinglab', 'local_alphatrade'),
        'eyebrow' => get_string('backtestinglab', 'local_alphatrade'),
        'title' => get_string('newstrategy', 'local_alphatrade'),
        'lead' => get_string('newstrategy_lead', 'local_alphatrade'),
        'form' => $form->render(),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$strategies = $DB->get_records('local_alphatrade_strategy', ['userid' => $USER->id], 'timemodified DESC');
$results = backtest::get_results_for(array_keys($strategies));
$cards = [];
foreach ($strategies as $strategy) {
    $cards[] = backtest::export_card($strategy, $results[$strategy->id]);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/backtesting', [
    'cards' => $cards,
    'hascards' => !empty($cards),
    'newurl' => (new moodle_url($listurl, ['action' => 'new']))->out(false),
    'toolsurl' => (new moodle_url('/local/alphatrade/tools.php'))->out(false),
]);
echo $OUTPUT->footer();
