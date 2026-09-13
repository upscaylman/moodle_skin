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
 * Payment and billing (maquette "Alpha Trade - Paiement"): current plan, payment method,
 * billing history with receipts, paid options through the Moodle payment gateways.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\billing;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/billing.php', 'billing', get_string('billing', 'local_alphatrade'), [], '',
    ['standalone' => ['ismessaging' => false, 'links' => []]]);

if (optional_param('paid', 0, PARAM_BOOL)) {
    \core\notification::success(get_string('paymentreceived', 'local_alphatrade'));
}

$datefmt = get_string('strftimedate', 'langconfig');
$payments = billing::get_payments($USER->id);
$programmecourse = programme::get_course();

// Current plan: the programme course (paid or enrolled), else the most recent payment.
$plan = null;
$programmepayments = $programmecourse ? array_filter($payments, function($payment) use ($programmecourse) {
    return $payment->courseid == $programmecourse->id;
}) : [];
if ($programmecourse && ($programmepayments || is_enrolled(context_course::instance($programmecourse->id), $USER, '', true))) {
    $context = context_course::instance($programmecourse->id);
    $last = $programmepayments ? reset($programmepayments) : null;
    $timeend = (int) $DB->get_field_sql("SELECT MAX(ue.timeend)
                                           FROM {user_enrolments} ue
                                           JOIN {enrol} e ON e.id = ue.enrolid
                                          WHERE e.courseid = ? AND ue.userid = ?", [$programmecourse->id, $USER->id]);
    $plan = [
        'name' => format_string($programmecourse->fullname, true, ['context' => $context]),
        'text' => $last ? get_string('plan_paid', 'local_alphatrade', [
                'amount' => billing::amount($last), 'date' => userdate($last->timecreated, $datefmt),
            ]) : get_string('plan_enrolled', 'local_alphatrade'),
        'isactive' => is_enrolled($context, $USER, '', true),
        'count' => $programmepayments ? get_string('paymentsdone', 'local_alphatrade', count($programmepayments)) : '',
    ];
    if ($timeend) {
        $plan['text'] .= ' ' . get_string('plan_until', 'local_alphatrade', userdate($timeend, $datefmt));
    }
} else if ($payments) {
    $last = reset($payments);
    $plan = [
        'name' => billing::description($last),
        'text' => get_string('plan_paid', 'local_alphatrade', [
            'amount' => billing::amount($last), 'date' => userdate($last->timecreated, $datefmt),
        ]),
        'isactive' => true,
        'count' => get_string('paymentsdone', 'local_alphatrade', count($payments)),
    ];
}

// Payment method: the gateway of the last payment (card details stay at the gateway).
$method = null;
if ($payments) {
    $last = reset($payments);
    $method = [
        'name' => billing::gateway_name($last->gateway),
        'text' => get_string('method_text', 'local_alphatrade', [
            'date' => userdate($last->timecreated, $datefmt),
            'gateway' => billing::gateway_name($last->gateway),
        ]),
    ];
}

$rows = [];
foreach ($payments as $payment) {
    $rows[] = [
        'date' => userdate($payment->timecreated, '%d/%m/%Y'),
        'description' => billing::description($payment),
        'amount' => billing::amount($payment),
        'receipturl' => (new moodle_url('/local/alphatrade/receipt.php', ['id' => $payment->id]))->out(false),
    ];
}

$offers = billing::get_offers($USER->id);
foreach ($offers as $index => &$offer) {
    $offer['primary'] = $index === 0;
}
unset($offer);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/billing', [
    'plan' => $plan,
    'hasplan' => (bool) $plan,
    'method' => $method,
    'hasmethod' => (bool) $method,
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'offers' => $offers,
    'hasoffers' => !empty($offers),
]);
echo $OUTPUT->footer();
