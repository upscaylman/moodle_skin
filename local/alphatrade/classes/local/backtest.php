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

use moodle_url;
use stdClass;

/**
 * Backtesting Lab data access.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backtest {

    /**
     * Owner, or a trainer allowed to see students' data.
     *
     * @param stdClass $strategy
     * @return bool
     */
    public static function can_view(stdClass $strategy): bool {
        global $USER;
        return $strategy->userid == $USER->id || page::is_staff();
    }

    /**
     * Chronological results (R) of a strategy.
     *
     * @param int $strategyid
     * @return float[]
     */
    public static function get_results(int $strategyid): array {
        global $DB;
        return array_map('floatval', $DB->get_fieldset_select('local_alphatrade_bttrade', 'resultr',
            'strategyid = ? ORDER BY tradedate ASC, id ASC', [$strategyid]));
    }

    /**
     * Results of many strategies at once, keyed by strategy id.
     *
     * @param int[] $strategyids
     * @return array
     */
    public static function get_results_for(array $strategyids): array {
        global $DB;
        $results = array_fill_keys($strategyids, []);
        if (!$strategyids) {
            return $results;
        }
        list($insql, $params) = $DB->get_in_or_equal($strategyids);
        $rs = $DB->get_recordset_select('local_alphatrade_bttrade', "strategyid $insql", $params,
            'strategyid ASC, tradedate ASC, id ASC', 'id, strategyid, resultr');
        foreach ($rs as $trade) {
            $results[$trade->strategyid][] = (float) $trade->resultr;
        }
        $rs->close();
        return $results;
    }

    /**
     * Strategy card (Backtesting Lab list, teacher view, final project).
     *
     * @param stdClass $strategy
     * @param float[] $results
     * @return array
     */
    public static function export_card(stdClass $strategy, array $results): array {
        $stats = stats::compute($results);
        $exported = stats::export($stats);
        return [
            'id' => $strategy->id,
            'name' => format_string($strategy->name),
            'market' => page::clean_symbol($strategy->market),
            'timeframe' => page::timeframe_label($strategy->timeframe),
            'url' => (new moodle_url('/local/alphatrade/strategy.php', ['id' => $strategy->id]))->out(false),
            'stats' => $exported,
            'trades' => $stats['trades'],
        ];
    }
}
