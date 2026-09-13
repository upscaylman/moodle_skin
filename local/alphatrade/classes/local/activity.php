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
 * Personal activity figures: streak, backtested trades, badges.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity {

    /**
     * Consecutive days (ending today or yesterday) with learning activity.
     *
     * @param int $userid
     * @return int
     */
    public static function get_streak(int $userid): int {
        global $DB;

        $since = usergetmidnight(time()) - 60 * DAYSECS;
        $days = [];
        try {
            $sql = "SELECT id, timecreated
                      FROM {logstore_standard_log}
                     WHERE userid = :userid AND timecreated >= :since AND edulevel = :edulevel";
            $rs = $DB->get_recordset_sql($sql, ['userid' => $userid, 'since' => $since, 'edulevel' => 2]);
            foreach ($rs as $record) {
                $days[usergetmidnight($record->timecreated)] = true;
            }
            $rs->close();
        } catch (\dml_exception $e) {
            return 0;
        }

        // Learning pages of this plugin do not log events: today's visit counts too.
        $days[usergetmidnight(time())] = true;

        $streak = 0;
        $day = usergetmidnight(time());
        while (isset($days[$day])) {
            $streak++;
            $day = usergetmidnight($day - DAYSECS / 2);
        }
        return $streak;
    }

    /**
     * Number of historical trades entered in the Backtesting Lab.
     *
     * @param int $userid
     * @return int
     */
    public static function count_backtested_trades(int $userid): int {
        global $DB;
        return $DB->count_records('local_alphatrade_bttrade', ['userid' => $userid]);
    }

    /**
     * Number of badges earned.
     *
     * @param int $userid
     * @return int
     */
    public static function count_badges(int $userid): int {
        global $CFG;
        if (empty($CFG->enablebadges)) {
            return 0;
        }
        require_once($CFG->libdir . '/badgeslib.php');
        return count(badges_get_user_badges($userid));
    }
}
