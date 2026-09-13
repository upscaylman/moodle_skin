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

namespace local_alphatrade\privacy;

use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: journal, backtests and chart analyses belong to the student (user context).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** @var array Table => exported fields. */
    const TABLES = [
        'local_alphatrade_strategy' => ['name', 'market', 'timeframe', 'period', 'description', 'timecreated', 'timemodified'],
        'local_alphatrade_bttrade' => ['strategyid', 'tradedate', 'direction', 'entry', 'stoploss', 'takeprofit', 'resultr', 'notes',
            'timecreated'],
        'local_alphatrade_journal' => ['tradedate', 'asset', 'assetclass', 'direction', 'setup', 'entry', 'stoploss', 'takeprofit',
            'riskpct', 'resultr', 'emotion', 'reason', 'marketcontext', 'followedplan', 'review', 'timecreated', 'timemodified'],
        'local_alphatrade_analysis' => ['chartid', 'structure', 'liquidity', 'bias', 'scenario', 'status', 'score', 'feedback',
            'timecreated', 'timemodified', 'timereviewed'],
    ];

    /**
     * Metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        foreach (self::TABLES as $table => $fields) {
            $metadata = ['userid' => 'privacy:metadata:userid'];
            foreach ($fields as $field) {
                $metadata[$field] = 'privacy:metadata:' . $field;
            }
            $collection->add_database_table($table, $metadata, 'privacy:metadata:' . $table);
        }
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');
        return $collection;
    }

    /**
     * Contexts with data: the user's own context.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        foreach (array_keys(self::TABLES) as $table) {
            if ($DB->record_exists($table, ['userid' => $userid])) {
                $contextlist->add_user_context($userid);
                break;
            }
        }
        return $contextlist;
    }

    /**
     * Users in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        foreach (array_keys(self::TABLES) as $table) {
            if ($DB->record_exists($table, ['userid' => $context->instanceid])) {
                $userlist->add_user($context->instanceid);
                return;
            }
        }
    }

    /**
     * Export.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_USER || $context->instanceid != $userid) {
                continue;
            }
            foreach (self::TABLES as $table => $fields) {
                $records = $DB->get_records($table, ['userid' => $userid], 'id ASC', 'id, ' . implode(', ', $fields));
                if ($records) {
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'local_alphatrade'), $table],
                        (object) ['records' => array_values($records)]
                    );
                }
            }
            writer::with_context($context)->export_area_files(
                [get_string('pluginname', 'local_alphatrade'), 'screenshots'], 'local_alphatrade', 'screenshot', false);
        }
    }

    /**
     * Delete everything in a context.
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        if ($context->contextlevel == CONTEXT_USER) {
            self::delete_user($context->instanceid);
        }
    }

    /**
     * Delete one user's data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $userid) {
                self::delete_user($userid);
            }
        }
    }

    /**
     * Delete several users' data.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        if (in_array($context->instanceid, $userlist->get_userids())) {
            self::delete_user($context->instanceid);
        }
    }

    /**
     * Remove all records and files of a user.
     *
     * @param int $userid
     */
    protected static function delete_user(int $userid): void {
        global $DB;
        foreach (array_keys(self::TABLES) as $table) {
            $DB->delete_records($table, ['userid' => $userid]);
        }
        $context = context_user::instance($userid, IGNORE_MISSING);
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'local_alphatrade', 'screenshot');
        }
    }
}
