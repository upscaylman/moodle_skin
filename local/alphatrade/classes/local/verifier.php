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

use stdClass;

/**
 * Checks backtested trades against real market data: which of the stop or the target was hit first.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verifier {

    /** @var int Bars scanned after the entry. */
    const BARS = 500;

    /** @var float Tolerance between the entered result and the market result, in R. */
    const TOLERANCE = 0.15;

    /**
     * Outcome of a trade from candles (pure function, unit testable).
     *
     * @param array $candles normalised candles, ascending
     * @param int $tradedate entry time
     * @param string $direction long|short
     * @param float $entry
     * @param float $stop
     * @param float $target
     * @return array ['status' => hit_tp|hit_sl|ambiguous|open|nodata, 'r' => float|null]
     */
    public static function outcome(array $candles, int $tradedate, string $direction, float $entry, float $stop,
            float $target): array {
        $risk = abs($entry - $stop);
        if ($risk <= 0) {
            return ['status' => 'nodata', 'r' => null];
        }
        $reward = abs($target - $entry) / $risk;
        $islong = $direction !== 'short';
        $seen = false;
        foreach ($candles as $candle) {
            if ($candle['t'] < $tradedate) {
                continue;
            }
            $seen = true;
            $hitstop = $islong ? $candle['l'] <= $stop : $candle['h'] >= $stop;
            $hittarget = $islong ? $candle['h'] >= $target : $candle['l'] <= $target;
            if ($hitstop && $hittarget) {
                // Both inside one bar: the order cannot be known, count the stop (conservative).
                return ['status' => 'ambiguous', 'r' => -1.0];
            }
            if ($hitstop) {
                return ['status' => 'hit_sl', 'r' => -1.0];
            }
            if ($hittarget) {
                return ['status' => 'hit_tp', 'r' => round($reward, 2)];
            }
        }
        return ['status' => $seen ? 'open' : 'nodata', 'r' => null];
    }

    /**
     * Verify every trade of a strategy that has entry, stop and target.
     *
     * @param stdClass $strategy
     * @return array counts per status
     */
    public static function verify_strategy(stdClass $strategy): array {
        global $DB;

        $preset = markets::find($strategy->market);
        if (!$preset || $preset['lse'] === '' || !marketdata::is_configured()) {
            throw new \moodle_exception('marketdata_notconfigured', 'local_alphatrade');
        }
        $seconds = marketdata::SECONDS[marketdata::TIMEFRAMES[$strategy->timeframe] ?? '1h'] ?? 3600;
        $counts = ['confirmed' => 0, 'mismatch' => 0, 'open' => 0, 'nodata' => 0];

        $trades = $DB->get_records_select('local_alphatrade_bttrade',
            'strategyid = ? AND entry IS NOT NULL AND stoploss IS NOT NULL AND takeprofit IS NOT NULL',
            [$strategy->id], 'tradedate ASC');
        foreach ($trades as $trade) {
            $start = (int) $trade->tradedate;
            $candles = marketdata::candles($preset['lse'], $strategy->timeframe, $start, $start + $seconds * self::BARS,
                self::BARS, 'asc');
            $result = self::outcome($candles, $start, $trade->direction, (float) $trade->entry, (float) $trade->stoploss,
                (float) $trade->takeprofit);

            if ($result['r'] === null) {
                $status = $result['status'];
            } else {
                $status = abs($result['r'] - (float) $trade->resultr) <= self::TOLERANCE ? 'confirmed' : 'mismatch';
            }
            $counts[$status] = ($counts[$status] ?? 0) + 1;
            $DB->update_record('local_alphatrade_bttrade', (object) [
                'id' => $trade->id,
                'verifiedr' => $result['r'],
                'verifystatus' => $status,
                'timeverified' => time(),
            ]);
        }
        return $counts;
    }
}
