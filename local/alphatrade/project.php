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
 * Final project "Alpha Trading System" (wireframe v3 screen 14).
 * Steps = activities of the project section; results = the student's most tested strategy.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\backtest;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/project.php', 'parcours', get_string('finalproject', 'local_alphatrade'));

$data = ['hasproject' => false];
$programme = programme::for_user($USER->id);
$module = $programme ? $programme->get_project_module() : null;

if ($module) {
    $steps = [];
    $current = null;
    $number = 0;
    foreach ($module['items'] as $item) {
        $number++;
        $item['number'] = sprintf('%02d', $number);
        $steps[] = $item;
        if (!$current && $item['iscurrent']) {
            $current = $item;
        }
    }
    $data = [
        'hasproject' => true,
        'percent' => $module['percent'],
        'steps' => $steps,
        'hassteps' => !empty($steps),
        'moduleurl' => $module['url'],
        'islocked' => $module['islocked'],
        'availableinfo' => $module['availableinfo'],
        'continueurl' => $current ? $current['url'] : '',
    ];
}

// Results: the strategy with the most backtested trades.
$sql = "SELECT s.*, (SELECT COUNT(1) FROM {local_alphatrade_bttrade} t WHERE t.strategyid = s.id) AS tradecount
          FROM {local_alphatrade_strategy} s
         WHERE s.userid = :userid
      ORDER BY tradecount DESC, s.timemodified DESC";
$best = $DB->get_records_sql($sql, ['userid' => $USER->id], 0, 1);
if ($best) {
    $strategy = reset($best);
    unset($strategy->tradecount);
    $data['result'] = backtest::export_card($strategy, backtest::get_results($strategy->id));
}
$data['backtestingurl'] = (new moodle_url('/local/alphatrade/backtesting.php'))->out(false);
$data['certificateurl'] = (new moodle_url('/local/alphatrade/certificate.php'))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/project', $data);
echo $OUTPUT->footer();
