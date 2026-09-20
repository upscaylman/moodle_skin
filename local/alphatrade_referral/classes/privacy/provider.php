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

namespace local_alphatrade_referral\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Confidentialité du moteur de parrainage.
 *
 * Une règle particulière s'applique ici : les conversions et les récompenses ne sont pas
 * supprimées à la demande d'effacement, elles sont anonymisées. Ce sont des pièces comptables
 * (§ 10.2), et l'architecture interdit la suppression d'historique.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Données stockées.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_atref_codes', [
            'code' => 'privacy:metadata:codes:code',
            'status' => 'privacy:metadata:codes:status',
            'timecreated' => 'privacy:metadata:codes:timecreated',
        ], 'privacy:metadata:codes');

        $collection->add_database_table('local_atref_relations', [
            'referrerid' => 'privacy:metadata:relations:referrerid',
            'referredid' => 'privacy:metadata:relations:referredid',
            'status' => 'privacy:metadata:relations:status',
        ], 'privacy:metadata:relations');

        $collection->add_database_table('local_atref_conversions', [
            'amount' => 'privacy:metadata:conversions:amount',
            'status' => 'privacy:metadata:conversions:status',
        ], 'privacy:metadata:conversions');

        $collection->add_database_table('local_atref_rewards', [
            'amount' => 'privacy:metadata:rewards:amount',
            'status' => 'privacy:metadata:rewards:status',
        ], 'privacy:metadata:rewards');

        $collection->add_database_table('local_atref_discord', [
            'discorduserid' => 'privacy:metadata:discord:discorduserid',
            'timelinked' => 'privacy:metadata:discord:timelinked',
        ], 'privacy:metadata:discord');

        $collection->add_external_location_link('discord.com', [
            'discorduserid' => 'privacy:metadata:discordapi:discorduserid',
            'content' => 'privacy:metadata:discordapi:content',
        ], 'privacy:metadata:discordapi');

        return $collection;
    }

    /**
     * Contextes où l'utilisateur a des données : le contexte système uniquement.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $has = $DB->record_exists('local_atref_codes', ['userid' => $userid])
            || $DB->record_exists_select('local_atref_relations', 'referrerid = ? OR referredid = ?',
                [$userid, $userid]);
        if ($has) {
            $contextlist->add_from_sql('SELECT id FROM {context} WHERE contextlevel = :level',
                ['level' => CONTEXT_SYSTEM]);
        }
        return $contextlist;
    }

    /**
     * Utilisateurs présents dans un contexte.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_atref_codes}', []);
        $userlist->add_from_sql('referrerid', 'SELECT referrerid FROM {local_atref_relations}', []);
        $userlist->add_from_sql('referredid', 'SELECT referredid FROM {local_atref_relations}', []);
    }

    /**
     * Export des données d'un utilisateur.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            $path = [get_string('pluginname', 'local_alphatrade_referral')];

            $code = $DB->get_record('local_atref_codes', ['userid' => $userid]);
            if ($code) {
                writer::with_context($context)->export_data($path,
                    (object) ['code' => $code->code, 'status' => $code->status,
                        'timecreated' => transform::datetime($code->timecreated)]);
            }

            $relations = $DB->get_records_select('local_atref_relations',
                'referrerid = ? OR referredid = ?', [$userid, $userid]);
            $rows = [];
            foreach ($relations as $relation) {
                $rows[] = (object) [
                    'role' => $relation->referrerid == $userid ? 'referrer' : 'referred',
                    'status' => $relation->status,
                    'timecreated' => transform::datetime($relation->timecreated),
                ];
            }
            if ($rows) {
                writer::with_context($context)->export_data(array_merge($path, ['relations']),
                    (object) ['relations' => $rows]);
            }

            $rewards = $DB->get_records('local_atref_rewards', ['referrerid' => $userid]);
            $rows = [];
            foreach ($rewards as $reward) {
                $rows[] = (object) ['amount' => $reward->amount, 'currency' => $reward->currency,
                    'status' => $reward->status, 'timecreated' => transform::datetime($reward->timecreated)];
            }
            if ($rows) {
                writer::with_context($context)->export_data(array_merge($path, ['rewards']),
                    (object) ['rewards' => $rows]);
            }
        }
    }

    /**
     * Effacement pour tout un contexte : hors de question de vider les tables du site.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // Rien : les conversions et récompenses sont des pièces comptables, et le journal d'audit
        // ne se supprime pas. L'effacement d'un utilisateur passe par l'anonymisation ci-dessous.
        return;
    }

    /**
     * Effacement pour un utilisateur : le code est désactivé, la liaison Discord supprimée,
     * l'historique commercial conservé mais détaché de toute donnée personnelle.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        self::anonymise([$contextlist->get_user()->id]);
    }

    /**
     * Effacement pour une liste d'utilisateurs.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        self::anonymise($userlist->get_userids());
    }

    /**
     * Désactive les codes et coupe la liaison Discord, sans toucher à l'historique commercial.
     *
     * @param array $userids
     * @return void
     */
    protected static function anonymise(array $userids): void {
        global $DB;

        if (!$userids) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $DB->set_field_select('local_atref_codes', 'status', 'disabled', "userid $insql", $params);
        $DB->delete_records_select('local_atref_discord', "userid $insql", $params);
        $DB->delete_records_select('local_atref_queue', "userid $insql", $params);
    }
}
