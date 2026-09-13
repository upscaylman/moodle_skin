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

namespace local_alphatrade\local;

/**
 * Backtest statistics, all expressed in R multiples, plus the equity curve drawn as SVG.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stats {

    /** Below this sample size no statistical edge can be claimed. */
    const MIN_SAMPLE = 30;

    /**
     * Compute statistics from chronological results in R.
     *
     * @param float[] $results
     * @return array
     */
    public static function compute(array $results): array {
        $results = array_values(array_map('floatval', $results));
        $trades = count($results);
        $wins = array_values(array_filter($results, function($r) {
            return $r > 0;
        }));
        $losses = array_values(array_filter($results, function($r) {
            return $r < 0;
        }));

        $sumwins = array_sum($wins);
        $sumlosses = abs(array_sum($losses));

        $equity = [0.0];
        $cumulative = 0.0;
        $peak = 0.0;
        $maxdrawdown = 0.0;
        $streak = 0;
        $maxstreak = 0;
        foreach ($results as $r) {
            $cumulative += $r;
            $equity[] = $cumulative;
            $peak = max($peak, $cumulative);
            $maxdrawdown = max($maxdrawdown, $peak - $cumulative);
            $streak = $r < 0 ? $streak + 1 : 0;
            $maxstreak = max($maxstreak, $streak);
        }

        return [
            'trades' => $trades,
            'wins' => count($wins),
            'losses' => count($losses),
            'winrate' => $trades ? 100 * count($wins) / $trades : null,
            'avgwin' => $wins ? $sumwins / count($wins) : null,
            'avgloss' => $losses ? $sumlosses / count($losses) : null,
            'expectancy' => $trades ? array_sum($results) / $trades : null,
            'profitfactor' => $sumlosses > 0 ? $sumwins / $sumlosses : null,
            'maxdrawdown' => $maxdrawdown,
            'maxconsecutivelosses' => $maxstreak,
            'totalr' => $cumulative,
            'equity' => $equity,
        ];
    }

    /**
     * Sober verdict: never "winning strategy", only what the sample demonstrates.
     *
     * @param array $stats
     * @return array [key, level] level is success|warning|neutral
     */
    public static function verdict(array $stats): array {
        if ($stats['trades'] < self::MIN_SAMPLE) {
            return ['verdict_small', 'neutral'];
        }
        if ($stats['expectancy'] > 0 && ($stats['profitfactor'] === null || $stats['profitfactor'] > 1)) {
            return ['verdict_positive', 'success'];
        }
        return ['verdict_negative', 'warning'];
    }

    /**
     * Template data for the stats block.
     *
     * @param array $stats
     * @return array
     */
    public static function export(array $stats): array {
        $percent = function(?float $value): string {
            return $value === null ? '-' : format_float($value, 1, true, true) . ' %';
        };
        $plain = function(?float $value): string {
            return $value === null ? '-' : format_float($value, 2, true, true);
        };
        list($verdictkey, $verdictlevel) = self::verdict($stats);

        return [
            'trades' => $stats['trades'],
            'winrate' => $percent($stats['winrate']),
            'expectancy' => page::format_r($stats['expectancy']),
            'expectancyclass' => page::value_class($stats['expectancy']),
            'avgwin' => $stats['avgwin'] === null ? '-' : page::format_r($stats['avgwin']),
            'avgloss' => $stats['avgloss'] === null ? '-' : page::format_r(-$stats['avgloss']),
            'profitfactor' => $plain($stats['profitfactor']),
            'maxdrawdown' => $stats['trades'] ? page::format_r(-$stats['maxdrawdown']) : '-',
            'maxconsecutivelosses' => $stats['maxconsecutivelosses'],
            'totalr' => page::format_r($stats['totalr']),
            'totalrclass' => page::value_class($stats['totalr']),
            'verdict' => get_string($verdictkey, 'local_alphatrade'),
            'verdictlevel' => $verdictlevel,
        ];
    }

    /**
     * Equity curve as SVG template data (no JS chart library).
     *
     * @param float[] $equity cumulative R, starting at 0
     * @param int $width
     * @param int $height
     * @return array
     */
    public static function equity_svg(array $equity, int $width = 720, int $height = 260): array {
        $padleft = 44;
        $padright = 12;
        $padtop = 14;
        $padbottom = 22;
        $min = min(0, min($equity));
        $max = max(0, max($equity));
        if ($max - $min < 1) {
            $max += 0.5;
            $min -= 0.5;
        }
        $count = max(1, count($equity) - 1);
        $plotw = $width - $padleft - $padright;
        $ploth = $height - $padtop - $padbottom;

        $x = function(int $i) use ($padleft, $plotw, $count): float {
            return round($padleft + $plotw * $i / $count, 2);
        };
        $y = function(float $value) use ($padtop, $ploth, $min, $max): float {
            return round($padtop + $ploth * ($max - $value) / ($max - $min), 2);
        };

        $points = [];
        foreach ($equity as $i => $value) {
            $points[] = $x($i) . ',' . $y($value);
        }
        $line = 'M' . implode(' L', $points);
        $zeroy = $y(0);
        $area = $line . ' L' . $x(count($equity) - 1) . ',' . $zeroy . ' L' . $x(0) . ',' . $zeroy . ' Z';

        $grid = [];
        $steps = 4;
        for ($s = 0; $s <= $steps; $s++) {
            $value = $max - ($max - $min) * $s / $steps;
            $grid[] = [
                'y' => $y($value),
                'x1' => $padleft,
                'x2' => $width - $padright,
                'labelx' => $padleft - 8,
                'label' => format_float($value, abs($max - $min) < 8 ? 1 : 0, true, true) . 'R',
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'line' => $line,
            'area' => $area,
            'zeroy' => $zeroy,
            'x1' => $padleft,
            'x2' => $width - $padright,
            'grid' => $grid,
            'hasdata' => count($equity) > 1,
        ];
    }
}
