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

use context_course;
use core_payment\helper;
use moodle_url;
use stdClass;

/**
 * Billing data from Moodle payments (core_payment) and paid enrolments (enrol_fee).
 * No card data ever reaches Moodle: the payment gateway (PayPal, Stripe...) keeps it.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class billing {

    /**
     * Payments of a user, most recent first, with the paid course when it is an enrolment fee.
     *
     * @param int $userid
     * @return stdClass[]
     */
    public static function get_payments(int $userid): array {
        global $DB;
        $sql = "SELECT p.*, e.courseid
                  FROM {payments} p
             LEFT JOIN {enrol} e ON e.id = p.itemid AND e.enrol = 'fee' AND p.component = 'enrol_fee'
                 WHERE p.userid = :userid
              ORDER BY p.timecreated DESC, p.id DESC";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Formatted amount.
     *
     * @param stdClass $payment
     * @return string
     */
    public static function amount(stdClass $payment): string {
        return helper::get_cost_as_string((float) $payment->amount, $payment->currency);
    }

    /**
     * Human description of a payment.
     *
     * @param stdClass $payment
     * @return string
     */
    public static function description(stdClass $payment): string {
        global $DB;
        if (!empty($payment->courseid) && ($course = $DB->get_record('course', ['id' => $payment->courseid], 'id, fullname'))) {
            return format_string($course->fullname, true, ['context' => context_course::instance($course->id)]);
        }
        $manager = get_string_manager();
        return $manager->string_exists('pluginname', $payment->component)
            ? get_string('pluginname', $payment->component) : $payment->component;
    }

    /**
     * Gateway name (PayPal, Stripe...).
     *
     * @param string $gateway
     * @return string
     */
    public static function gateway_name(string $gateway): string {
        return get_string_manager()->string_exists('pluginname', 'paygw_' . $gateway)
            ? get_string('pluginname', 'paygw_' . $gateway) : $gateway;
    }

    /**
     * Receipt number shown to the student.
     *
     * @param stdClass $payment
     * @return string
     */
    public static function receipt_number(stdClass $payment): string {
        return 'AT-' . userdate($payment->timecreated, '%Y') . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Paid enrolments the user can still buy (options of the maquette "Ajouter un module").
     *
     * @param int $userid
     * @return array
     */
    public static function get_offers(int $userid): array {
        global $DB;
        if (!enrol_is_enabled('fee')) {
            return [];
        }
        $now = time();
        $sql = "SELECT e.id, e.courseid, e.cost, e.currency, e.enrolstartdate, e.enrolenddate, c.fullname, c.summary,
                       c.summaryformat, c.visible
                  FROM {enrol} e
                  JOIN {course} c ON c.id = e.courseid
                 WHERE e.enrol = 'fee' AND e.status = :enabled
                       AND (e.enrolstartdate = 0 OR e.enrolstartdate <= :now1)
                       AND (e.enrolenddate = 0 OR e.enrolenddate > :now2)
                       AND NOT EXISTS (SELECT 1 FROM {user_enrolments} ue WHERE ue.enrolid = e.id AND ue.userid = :userid)
              ORDER BY c.sortorder ASC";
        $records = $DB->get_records_sql($sql, ['enabled' => ENROL_INSTANCE_ENABLED, 'now1' => $now, 'now2' => $now,
            'userid' => $userid]);
        $programmeid = (int) get_config('local_alphatrade', 'programmecourse');
        $defaultcost = (float) get_config('enrol_fee', 'cost');
        $offers = [];
        foreach ($records as $record) {
            $context = context_course::instance($record->courseid);
            if (!$record->visible && !has_capability('moodle/course:viewhiddencourses', $context, $userid)) {
                continue;
            }
            $cost = (float) $record->cost > 0 ? (float) $record->cost : $defaultcost;
            if ($cost < 0.01) {
                continue;
            }
            $name = format_string($record->fullname, true, ['context' => $context]);
            $summary = format_text($record->summary, $record->summaryformat, ['context' => $context]);
            $offers[] = [
                'isprogramme' => $record->courseid == $programmeid,
                'name' => $name,
                'text' => shorten_text(trim(html_to_text($summary, 0, false)), 160),
                'price' => helper::get_cost_as_string($cost, $record->currency),
                'instanceid' => $record->id,
                'description' => get_string('purchasedescription', 'enrol_fee', $name),
                'successurl' => (new moodle_url('/local/alphatrade/billing.php', ['paid' => 1]))->out(false),
            ];
        }
        // The programme first.
        usort($offers, function($a, $b) {
            return (int) $b['isprogramme'] <=> (int) $a['isprogramme'];
        });
        return $offers;
    }
}
