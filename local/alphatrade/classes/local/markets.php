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
 * Market presets of the Backtesting Lab (maquette pills): label, TradingView symbol, market data symbol.
 * Configured as "label|tradingview|lse" lines in the plugin settings.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class markets {

    /** @var string Default presets (maquette). */
    const DEFAULT = "EUR/USD|FX:EURUSD|EUR/USD\nGBP/USD|FX:GBPUSD|GBP/USD\nXAU/USD|OANDA:XAUUSD|XAU/USD\n" .
        "BTC/USD|BINANCE:BTCUSDT|BTC/USD\nUS30|FOREXCOM:DJI|US30/USD";

    /** @var string[] Timeframe pills of the maquette (TradingView intervals). */
    const TIMEFRAMES = ['15' => 'M15', '60' => 'H1', '240' => 'H4', 'D' => 'D1'];

    /**
     * All presets.
     *
     * @return array list of ['label', 'tv', 'lse']
     */
    public static function all(): array {
        $config = (string) get_config('local_alphatrade', 'markets');
        $lines = preg_split('/\R/', trim($config) !== '' ? $config : self::DEFAULT);
        $presets = [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2 || $parts[0] === '') {
                continue;
            }
            $presets[] = [
                'label' => $parts[0],
                'tv' => page::clean_symbol($parts[1]),
                'lse' => $parts[2] ?? '',
            ];
        }
        return $presets;
    }

    /**
     * Preset by label (or by TradingView symbol for strategies created before presets).
     *
     * @param string $market
     * @return array|null
     */
    public static function find(string $market): ?array {
        foreach (self::all() as $preset) {
            if ($preset['label'] === $market || $preset['tv'] === page::clean_symbol($market)) {
                return $preset;
            }
        }
        return null;
    }

    /**
     * TradingView symbol of a stored market value.
     *
     * @param string $market
     * @return string
     */
    public static function tv_symbol(string $market): string {
        $preset = self::find($market);
        return $preset ? $preset['tv'] : page::clean_symbol($market);
    }

    /**
     * Display label of a stored market value.
     *
     * @param string $market
     * @return string
     */
    public static function label(string $market): string {
        $preset = self::find($market);
        return $preset ? $preset['label'] : page::display_symbol($market);
    }
}
