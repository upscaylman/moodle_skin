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

use context_system;
use moodle_url;

/**
 * Shared page setup for the Alpha Trade app pages.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page {

    /**
     * Require a real (non guest) login and set up an Alpha Trade page.
     *
     * @param string $path e.g. /local/alphatrade/journal.php
     * @param string $navkey active navigation item (home, parcours, practice, backtest, journal...)
     * @param string $title
     * @param array $params URL params
     */
    public static function setup(string $path, string $navkey, string $title, array $params = []): void {
        global $PAGE;

        require_login(null, false);
        if (isguestuser()) {
            redirect(get_login_url());
        }

        $PAGE->set_context(context_system::instance());
        $PAGE->set_url(new moodle_url($path, $params));
        $PAGE->set_pagelayout('standard');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->add_body_class('alpha-page');
        $PAGE->add_body_class('alpha-nav-' . $navkey);
    }

    /**
     * Programme course context, or system context when the programme is not configured.
     *
     * @return \context
     */
    public static function programme_context(): \context {
        $course = programme::get_course();
        return $course ? \context_course::instance($course->id) : context_system::instance();
    }

    /**
     * Can the current user see other students' data (teachers of the programme)?
     *
     * @param string $capability
     * @return bool
     */
    public static function is_staff(string $capability = 'local/alphatrade:viewstudentdata'): bool {
        return has_capability($capability, self::programme_context());
    }

    /**
     * Format a signed R multiple: +1.5R / -1R / 0R.
     *
     * @param float|null $value
     * @param int $decimals
     * @return string
     */
    public static function format_r(?float $value, int $decimals = 2): string {
        if ($value === null) {
            return '-';
        }
        $formatted = format_float(abs($value), $decimals, true, true);
        $sign = $value > 0 ? '+' : ($value < 0 ? '-' : '');
        return $sign . $formatted . 'R';
    }

    /**
     * CSS class for a signed value.
     *
     * @param float|null $value
     * @return string
     */
    public static function value_class(?float $value): string {
        if ($value === null || $value == 0) {
            return '';
        }
        return $value > 0 ? 'alpha-value-positive' : 'alpha-value-negative';
    }

    /**
     * Clean a TradingView symbol (EXCHANGE:TICKER).
     *
     * @param string $symbol
     * @return string
     */
    public static function clean_symbol(string $symbol): string {
        return strtoupper(preg_replace('/[^A-Za-z0-9:._!\-]/', '', $symbol));
    }

    /**
     * TradingView free embed (no API key, read only). Only the symbol and interval vary.
     *
     * @param string $symbol
     * @param string $interval 1, 5, 15, 60, 240, D, W
     * @return string
     */
    public static function tradingview_url(string $symbol, string $interval): string {
        $interval = in_array($interval, array_keys(self::timeframes())) ? $interval : '60';
        $params = [
            'symbol' => self::clean_symbol($symbol),
            'interval' => $interval,
            'theme' => 'dark',
            'style' => '1',
            'locale' => 'fr',
            'toolbarbg' => '181a1e',
            'hide_side_toolbar' => '0',
            'allow_symbol_change' => '0',
            'saveimage' => '0',
            'withdateranges' => '1',
        ];
        return 'https://s.tradingview.com/widgetembed/?' . http_build_query($params, '', '&');
    }

    /**
     * Timeframes offered in forms.
     *
     * @return array
     */
    public static function timeframes(): array {
        return [
            '1' => 'M1',
            '5' => 'M5',
            '15' => 'M15',
            '30' => 'M30',
            '60' => 'H1',
            '240' => 'H4',
            'D' => 'D1',
            'W' => 'W1',
        ];
    }

    /**
     * Human label of a timeframe.
     *
     * @param string $interval
     * @return string
     */
    public static function timeframe_label(string $interval): string {
        return self::timeframes()[$interval] ?? $interval;
    }
}
