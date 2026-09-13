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

    /** @var array|null In-page header (title, subtitle, standalone nav) read by theme_alphatrade. */
    protected static $header = null;

    /**
     * Require a real (non guest) login and set up an Alpha Trade page.
     *
     * @param string $path e.g. /local/alphatrade/journal.php
     * @param string $navkey active navigation item (home, parcours, practice, backtest, journal...)
     * @param string $title page title shown in the in-page header (maquette)
     * @param array $params URL params
     * @param string $subtitle line under the title
     * @param array $options 'standalone' => ['ismessaging' => bool, 'links' => [...]] for site-style pages
     */
    public static function setup(string $path, string $navkey, string $title, array $params = [], string $subtitle = '',
            array $options = []): void {
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
        if (!empty($options['standalone'])) {
            $PAGE->add_body_class('alpha-standalone');
        }
        self::$header = [
            'title' => $title,
            'subtitle' => $subtitle,
            'standalone' => $options['standalone'] ?? [],
        ];
    }

    /**
     * Change the in-page header after setup (e.g. once the record is loaded).
     *
     * @param string $title
     * @param string $subtitle
     */
    public static function set_header(string $title, string $subtitle = ''): void {
        global $PAGE;
        self::$header = array_merge(self::$header ?? [], ['title' => $title, 'subtitle' => $subtitle]);
        $PAGE->set_title($title);
    }

    /**
     * In-page header declared by the current page, if any.
     *
     * @return array|null
     */
    public static function get_header(): ?array {
        return self::$header;
    }

    /**
     * Trading figure with a dot decimal as in the maquettes: 48.2, +0.38R.
     *
     * @param float|null $value
     * @param int $decimals
     * @return string
     */
    public static function num(?float $value, int $decimals = 1): string {
        return $value === null ? '-' : number_format($value, $decimals, '.', ' ');
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
    public static function format_r(?float $value, int $decimals = 2, bool $trim = true): string {
        if ($value === null) {
            return '-';
        }
        $formatted = number_format(abs($value), $decimals, '.', '');
        if ($trim && strpos($formatted, '.') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
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
        return $value > 0 ? 'at-positive' : 'at-negative';
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
     * Human label of a market symbol as in the maquettes: FX:EURUSD -> EUR/USD, BINANCE:BTCUSDT -> BTC/USD.
     *
     * @param string $symbol
     * @return string
     */
    public static function display_symbol(string $symbol): string {
        $clean = self::clean_symbol($symbol);
        $ticker = strpos($clean, ':') !== false ? substr($clean, strpos($clean, ':') + 1) : $clean;
        if (strpos($ticker, '/') !== false) {
            return $ticker;
        }
        if (preg_match('/^([A-Z]{3,4})(USDT|USDC)$/', $ticker, $m)) {
            return $m[1] . '/USD';
        }
        if (preg_match('/^[A-Z]{6}$/', $ticker)) {
            return substr($ticker, 0, 3) . '/' . substr($ticker, 3);
        }
        return $ticker;
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
        // Same embed as the maquette (www.tradingview.com/widgetembed).
        $params = [
            'symbol' => self::clean_symbol($symbol),
            'interval' => $interval,
            'hidesidetoolbar' => '1',
            'symboledit' => '0',
            'saveimage' => '0',
            'toolbarbg' => '1a1a1a',
            'theme' => 'dark',
            'style' => '1',
            'timezone' => 'Etc/UTC',
            'locale' => 'fr',
        ];
        return 'https://www.tradingview.com/widgetembed/?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
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

    /**
     * Short relative time of the maquettes: "Il y a 2h", "Hier", "Il y a 3 jours".
     *
     * @param int $time
     * @return string
     */
    public static function ago(int $time): string {
        $diff = max(0, time() - $time);
        if ($diff < HOURSECS) {
            return get_string('ago_minutes', 'local_alphatrade', max(1, (int) floor($diff / MINSECS)));
        }
        if ($time >= usergetmidnight(time())) {
            return get_string('ago_hours', 'local_alphatrade', (int) floor($diff / HOURSECS));
        }
        if ($time >= usergetmidnight(time()) - DAYSECS) {
            return get_string('ago_yesterday', 'local_alphatrade');
        }
        if ($diff < 30 * DAYSECS) {
            return get_string('ago_days', 'local_alphatrade', max(2, (int) ceil($diff / DAYSECS)));
        }
        return userdate($time, '%d/%m/%Y');
    }

    /**
     * Initials of a user (avatar fallback).
     *
     * @param \stdClass $user
     * @return string
     */
    public static function initials(\stdClass $user): string {
        $first = \core_text::substr(trim((string) ($user->firstname ?? '')), 0, 1);
        $last = \core_text::substr(trim((string) ($user->lastname ?? '')), 0, 1);
        return \core_text::strtoupper($first . $last) ?: '?';
    }
}
