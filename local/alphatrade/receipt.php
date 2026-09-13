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

/**
 * Printable receipt of a Moodle payment (owner or site administrator only).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\billing;
use local_alphatrade\local\page;

$id = required_param('id', PARAM_INT);

page::setup('/local/alphatrade/receipt.php', 'billing', get_string('receipt', 'local_alphatrade'), ['id' => $id], '',
    ['standalone' => ['ismessaging' => false, 'links' => [
        ['url' => (new moodle_url('/local/alphatrade/billing.php'))->out(false), 'icon' => 'ph-arrow-left',
            'label' => get_string('billing', 'local_alphatrade'), 'active' => false, 'badge' => ''],
    ]]]);

$payments = billing::get_payments($USER->id);
$payment = $payments[$id] ?? null;
if (!$payment) {
    require_capability('moodle/site:config', context_system::instance());
    $sql = "SELECT p.*, e.courseid
              FROM {payments} p
         LEFT JOIN {enrol} e ON e.id = p.itemid AND e.enrol = 'fee' AND p.component = 'enrol_fee'
             WHERE p.id = :id";
    $payment = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);
}
$payer = core_user::get_user($payment->userid, '*', MUST_EXIST);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/receipt', [
    'number' => billing::receipt_number($payment),
    'date' => userdate($payment->timecreated, get_string('strftimedatetime', 'langconfig')),
    'sitename' => format_string($SITE->fullname),
    'payer' => fullname($payer),
    'email' => $payer->email,
    'description' => billing::description($payment),
    'gateway' => billing::gateway_name($payment->gateway),
    'amount' => billing::amount($payment),
]);
echo $OUTPUT->footer();
