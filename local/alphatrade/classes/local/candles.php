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
 * Candlestick chart drawn server side as SVG from proxied market data (no chart library, no key in the page).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class candles {

    /**
     * Template data for an SVG candlestick chart.
     *
     * @param array $rows normalised candles (ascending)
     * @param int $width
     * @param int $height
     * @return array
     */
    public static function svg(array $rows, int $width = 960, int $height = 340): array {
        $rows = array_values($rows);
        $count = count($rows);
        if ($count < 2) {
            return ['hascandles' => false];
        }
        $padright = 64;
        $padv = 12;
        $plotw = $width - $padright;
        $ploth = $height - 2 * $padv;
        $high = max(array_column($rows, 'h'));
        $low = min(array_column($rows, 'l'));
        $range = ($high - $low) ?: 1;
        $step = $plotw / $count;
        $bodyw = max(1.0, $step * 0.62);

        $y = function(float $price) use ($padv, $ploth, $high, $range): float {
            return round($padv + ($high - $price) / $range * $ploth, 2);
        };

        $bars = [];
        foreach ($rows as $i => $row) {
            $x = $i * $step + $step / 2;
            $up = $row['c'] >= $row['o'];
            $top = $y(max($row['o'], $row['c']));
            $bottom = $y(min($row['o'], $row['c']));
            $bars[] = [
                'x' => round($x, 2),
                'bx' => round($x - $bodyw / 2, 2),
                'bw' => round($bodyw, 2),
                'wy1' => $y($row['h']),
                'wy2' => $y($row['l']),
                'by' => $top,
                'bh' => max(1, round($bottom - $top, 2)),
                'up' => $up,
            ];
        }

        $decimals = $high >= 1000 ? 0 : ($high >= 10 ? 2 : 4);
        $labels = [];
        for ($s = 0; $s <= 4; $s++) {
            $price = $high - $range * $s / 4;
            $labels[] = ['y' => $y($price), 'label' => number_format($price, $decimals, '.', ' '), 'x' => $plotw + 8, 'x2' => $plotw];
        }
        $last = end($rows);

        return [
            'hascandles' => true,
            'width' => $width,
            'height' => $height,
            'bars' => $bars,
            'labels' => $labels,
            'from' => userdate($rows[0]['t'], get_string('strftimedatetimeshort', 'langconfig')),
            'to' => userdate($last['t'], get_string('strftimedatetimeshort', 'langconfig')),
            'last' => number_format($last['c'], $decimals, '.', ' '),
        ];
    }
}
