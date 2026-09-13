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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Server-side proxy to the London Strategic Edge market data API.
 *
 * The API key never leaves the server: it is read from the plugin settings or from the
 * ALPHATRADE_LSE_API_KEY environment variable, and every browser-facing feature goes through
 * this class (server rendering or the local_alphatrade_get_candles web service).
 * Responses are cached (MUC) and calls are rate limited per user.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class marketdata {

    /** @var string Default API base URL (vault REST). */
    const DEFAULT_BASEURL = 'https://api.londonstrategicedge.com/vault';

    /** @var array TradingView interval => LSE timeframe. */
    const TIMEFRAMES = [
        '1' => '1m',
        '5' => '5m',
        '15' => '15m',
        '30' => '30m',
        '60' => '1h',
        '240' => '4h',
        'D' => '1d',
        'W' => '1w',
    ];

    /** @var array Seconds per timeframe, used to page through history. */
    const SECONDS = [
        '1m' => 60, '5m' => 300, '15m' => 900, '30m' => 1800, '1h' => 3600, '4h' => 14400, '1d' => 86400, '1w' => 604800,
    ];

    /**
     * Is an API key available?
     *
     * @return bool
     */
    public static function is_configured(): bool {
        return self::api_key() !== '';
    }

    /**
     * API key: plugin setting first, then the server environment. Never sent to the browser.
     *
     * @return string
     */
    protected static function api_key(): string {
        $key = trim((string) get_config('local_alphatrade', 'lse_apikey'));
        if ($key === '') {
            $key = trim((string) getenv('ALPHATRADE_LSE_API_KEY'));
        }
        return $key;
    }

    /**
     * Historical OHLCV candles.
     *
     * @param string $symbol LSE symbol, e.g. EUR/USD, XAU/USD, BTC/USD
     * @param string $interval TradingView interval (1, 5, 15, 30, 60, 240, D, W)
     * @param int|null $start unix time of the first bar
     * @param int|null $end unix time of the last bar
     * @param int $limit max bars (1..5000)
     * @param string $order asc or desc
     * @return array list of ['t' => int, 'o' => float, 'h' => float, 'l' => float, 'c' => float, 'v' => float]
     * @throws \moodle_exception
     */
    public static function candles(string $symbol, string $interval, ?int $start = null, ?int $end = null, int $limit = 500,
            string $order = 'asc'): array {
        global $USER;

        if (!self::is_configured()) {
            throw new \moodle_exception('marketdata_notconfigured', 'local_alphatrade');
        }
        if (!preg_match('~^[A-Za-z0-9./_\-]{1,32}$~', $symbol)) {
            throw new \moodle_exception('invalidsymbol', 'local_alphatrade');
        }
        if (!isset(self::TIMEFRAMES[$interval])) {
            throw new \moodle_exception('marketdata_badtimeframe', 'local_alphatrade');
        }
        $limit = max(1, min(5000, $limit));
        $order = $order === 'desc' ? 'desc' : 'asc';

        $params = [
            'symbol' => $symbol,
            'timeframe' => self::TIMEFRAMES[$interval],
            'order' => $order,
            'limit' => $limit,
        ];
        if ($start) {
            $params['start'] = gmdate('Y-m-d\TH:i:s', $start);
        }
        if ($end) {
            $params['end'] = gmdate('Y-m-d\TH:i:s', $end);
        }

        $cache = \cache::make('local_alphatrade', 'marketdata');
        $cachekey = sha1(json_encode($params));
        $cached = $cache->get($cachekey);
        $ttl = max(60, (int) (get_config('local_alphatrade', 'lse_cachettl') ?: 3600));
        if ($cached !== false && $cached['time'] > time() - $ttl) {
            return $cached['rows'];
        }

        self::check_rate_limit((int) ($USER->id ?? 0));

        $baseurl = rtrim((string) (get_config('local_alphatrade', 'lse_baseurl') ?: self::DEFAULT_BASEURL), '/');
        $curl = new \curl(['ignoresecurity' => false]);
        $curl->setHeader([
            'x-api-key: ' . self::api_key(),
            'Accept: application/json',
            'User-Agent: alphatrade-moodle (+local_alphatrade)',
        ]);
        $response = $curl->get($baseurl . '/candles', $params, ['CURLOPT_TIMEOUT' => 20, 'CURLOPT_CONNECTTIMEOUT' => 8]);
        $info = $curl->get_info();
        $status = (int) ($info['http_code'] ?? 0);
        if ($curl->get_errno() || $status < 200 || $status >= 300) {
            debugging('local_alphatrade market data error: HTTP ' . $status . ' ' . s(substr((string) $response, 0, 200)),
                DEBUG_DEVELOPER);
            throw new \moodle_exception('marketdata_error', 'local_alphatrade', '', $status);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new \moodle_exception('marketdata_error', 'local_alphatrade', '', 'json');
        }
        $rows = self::normalise(isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded);
        $cache->set($cachekey, ['time' => time(), 'rows' => $rows]);
        return $rows;
    }

    /**
     * Normalise API rows (ts/timestamp, open/o...) into compact candles sorted by time.
     *
     * @param array $rows
     * @return array
     */
    public static function normalise(array $rows): array {
        $candles = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $time = $row['ts'] ?? $row['timestamp'] ?? $row['time'] ?? $row['t'] ?? null;
            $open = $row['open'] ?? $row['o'] ?? null;
            $high = $row['high'] ?? $row['h'] ?? null;
            $low = $row['low'] ?? $row['l'] ?? null;
            $close = $row['close'] ?? $row['c'] ?? null;
            if ($time === null || $open === null || $high === null || $low === null || $close === null) {
                continue;
            }
            $unix = is_numeric($time) ? (int) ($time > 20000000000 ? $time / 1000 : $time) : strtotime((string) $time . ' UTC');
            if (!$unix) {
                continue;
            }
            $candles[] = [
                't' => $unix,
                'o' => (float) $open,
                'h' => (float) $high,
                'l' => (float) $low,
                'c' => (float) $close,
                'v' => (float) ($row['volume'] ?? $row['v'] ?? 0),
            ];
        }
        usort($candles, function($a, $b) {
            return $a['t'] <=> $b['t'];
        });
        return $candles;
    }

    /**
     * Per-user hourly quota of uncached calls.
     *
     * @param int $userid
     * @throws \moodle_exception
     */
    protected static function check_rate_limit(int $userid): void {
        $limit = (int) (get_config('local_alphatrade', 'lse_ratelimit') ?: 120);
        $cache = \cache::make('local_alphatrade', 'ratelimit');
        $key = 'u' . $userid . '_' . gmdate('YmdH');
        $count = (int) $cache->get($key);
        if ($count >= $limit) {
            throw new \moodle_exception('marketdata_ratelimited', 'local_alphatrade');
        }
        $cache->set($key, $count + 1);
    }
}
