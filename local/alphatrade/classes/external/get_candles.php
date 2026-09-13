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

namespace local_alphatrade\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_alphatrade\local\marketdata;
use local_alphatrade\local\markets;

/**
 * AJAX proxy: candles for a configured market. The browser never sees the API key.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_candles extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'market' => new external_value(PARAM_TEXT, 'Market label as configured, e.g. EUR/USD'),
            'timeframe' => new external_value(PARAM_ALPHANUM, 'TradingView interval: 1, 5, 15, 30, 60, 240, D, W'),
            'limit' => new external_value(PARAM_INT, 'Number of bars', VALUE_DEFAULT, 200),
            'end' => new external_value(PARAM_INT, 'Unix time of the last bar (0 = now)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $market
     * @param string $timeframe
     * @param int $limit
     * @param int $end
     * @return array
     */
    public static function execute(string $market, string $timeframe, int $limit = 200, int $end = 0): array {
        $params = self::validate_parameters(self::execute_parameters(),
            ['market' => $market, 'timeframe' => $timeframe, 'limit' => $limit, 'end' => $end]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_login(null, false);
        if (isguestuser()) {
            throw new \require_login_exception('guest');
        }

        $preset = markets::find($params['market']);
        if (!$preset || $preset['lse'] === '') {
            throw new \moodle_exception('invalidsymbol', 'local_alphatrade');
        }
        $rows = marketdata::candles($preset['lse'], $params['timeframe'], null, $params['end'] ?: null,
            max(1, min(1000, $params['limit'])), 'desc');
        return ['candles' => array_values($rows)];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'candles' => new external_multiple_structure(new external_single_structure([
                't' => new external_value(PARAM_INT, 'Bar open time (unix)'),
                'o' => new external_value(PARAM_FLOAT, 'Open'),
                'h' => new external_value(PARAM_FLOAT, 'High'),
                'l' => new external_value(PARAM_FLOAT, 'Low'),
                'c' => new external_value(PARAM_FLOAT, 'Close'),
                'v' => new external_value(PARAM_FLOAT, 'Volume'),
            ])),
        ]);
    }
}
