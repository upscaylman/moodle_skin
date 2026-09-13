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
 * Alpha Trade tools (wireframe v3 screen 11): risk, position size, risk/reward, statistics.
 * Calculations run server side (plain GET forms): no JS, no external service.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\form\trade_form;
use local_alphatrade\local\page;

$tool = optional_param('tool', '', PARAM_ALPHA);
$tool = in_array($tool, ['risk', 'position', 'rr']) ? $tool : '';

page::setup('/local/alphatrade/tools.php', 'tools', get_string('tools', 'local_alphatrade'), $tool ? ['tool' => $tool] : [],
    get_string('sub_tools', 'local_alphatrade'));
$baseurl = new moodle_url('/local/alphatrade/tools.php');

if (!$tool) {
    $cards = [];
    foreach (['risk' => 'ph-scales', 'position' => 'ph-ruler', 'rr' => 'ph-target'] as $key => $icon) {
        $cards[] = [
            'icon' => $icon,
            'title' => get_string('tool_' . $key, 'local_alphatrade'),
            'text' => get_string('tool_' . $key . '_desc', 'local_alphatrade'),
            'url' => (new moodle_url($baseurl, ['tool' => $key]))->out(false),
        ];
    }
    $cards[] = [
        'icon' => 'ph-chart-bar',
        'title' => get_string('tool_stats', 'local_alphatrade'),
        'text' => get_string('tool_stats_desc', 'local_alphatrade'),
        'url' => (new moodle_url('/local/alphatrade/backtesting.php'))->out(false),
    ];
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/tools', ['cards' => $cards]);
    echo $OUTPUT->footer();
    exit;
}

$fields = [
    'risk' => ['capital', 'riskpct'],
    'position' => ['capital', 'riskpct', 'entry', 'stoploss', 'pointvalue'],
    'rr' => ['entry', 'stoploss', 'takeprofit'],
];
$values = [];
foreach ($fields[$tool] as $field) {
    $values[$field] = trade_form::to_float(optional_param($field, '', PARAM_RAW_TRIMMED));
}
if ($tool === 'position' && $values['pointvalue'] === null) {
    $values['pointvalue'] = 1.0;
}

$number = function(?float $value, int $decimals = 2): string {
    return $value === null ? '-' : format_float($value, $decimals, true, true);
};

$results = [];
$warning = '';
$complete = !in_array(null, $values, true);
if ($complete) {
    if ($tool === 'risk' || $tool === 'position') {
        $amount = $values['capital'] * $values['riskpct'] / 100;
        $results[] = ['label' => get_string('riskamount', 'local_alphatrade'), 'value' => $number($amount), 'main' => $tool === 'risk'];
        if ($values['riskpct'] > 2) {
            $warning = get_string('riskwarning', 'local_alphatrade');
        }
    }
    if ($tool === 'position') {
        $distance = abs($values['entry'] - $values['stoploss']);
        if ($distance > 0 && $values['pointvalue'] > 0) {
            $size = $amount / ($distance * $values['pointvalue']);
            $results[] = ['label' => get_string('stopdistance', 'local_alphatrade'), 'value' => $number($distance, 5), 'main' => false];
            $results[] = ['label' => get_string('positionsize', 'local_alphatrade'), 'value' => $number($size, 4), 'main' => true];
        } else {
            $warning = get_string('invalidstop', 'local_alphatrade');
        }
    }
    if ($tool === 'rr') {
        $risk = abs($values['entry'] - $values['stoploss']);
        $reward = abs($values['takeprofit'] - $values['entry']);
        $islong = $values['takeprofit'] > $values['entry'];
        $coherent = $islong ? $values['stoploss'] < $values['entry'] : $values['stoploss'] > $values['entry'];
        if ($risk > 0 && $coherent) {
            $ratio = $reward / $risk;
            $results[] = ['label' => get_string('direction', 'local_alphatrade'),
                'value' => get_string($islong ? 'direction_long' : 'direction_short', 'local_alphatrade'), 'main' => false];
            $results[] = ['label' => get_string('rrratio', 'local_alphatrade'), 'value' => '1 : ' . $number($ratio), 'main' => true];
            $results[] = ['label' => get_string('breakevenwinrate', 'local_alphatrade'),
                'value' => $number(100 / (1 + $ratio), 1) . ' %', 'main' => false];
        } else {
            $warning = get_string('invalidstop', 'local_alphatrade');
        }
    }
}

$inputs = [];
foreach ($fields[$tool] as $field) {
    $raw = optional_param($field, '', PARAM_RAW_TRIMMED);
    $inputs[] = [
        'name' => $field,
        'label' => get_string('tool_field_' . $field, 'local_alphatrade'),
        'value' => $raw === '' && $field === 'pointvalue' ? '1' : $raw,
        'help' => get_string_manager()->string_exists('tool_field_' . $field . '_help', 'local_alphatrade')
            ? get_string('tool_field_' . $field . '_help', 'local_alphatrade') : '',
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/calculator', [
    'backurl' => $baseurl->out(false),
    'title' => get_string('tool_' . $tool, 'local_alphatrade'),
    'lead' => get_string('tool_' . $tool . '_desc', 'local_alphatrade'),
    'action' => $baseurl->out(false),
    'tool' => $tool,
    'inputs' => $inputs,
    'hasresults' => !empty($results),
    'results' => $results,
    'warning' => $warning,
]);
echo $OUTPUT->footer();
