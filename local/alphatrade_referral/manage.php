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
 * Administration du parrainage : file de revue manuelle et vue d'ensemble.
 * Chaque décision exige un motif, et elle est journalisée avec son auteur (§ 10.3.C).
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_alphatrade_referral\local\engine;

admin_externalpage_setup('local_alphatrade_referral_manage');
$context = context_system::instance();
require_capability('local/alphatrade_referral:view', $context);

$url = new moodle_url('/local/alphatrade_referral/manage.php');

// Décision de revue.
$decide = optional_param('decide', 0, PARAM_INT);
if ($decide && confirm_sesskey()) {
    require_capability('local/alphatrade_referral:validate', $context);
    $action = required_param('action', PARAM_ALPHA);
    $reason = trim(required_param('reason', PARAM_TEXT));
    if ($reason === '') {
        redirect($url, get_string('reasonrequired', 'local_alphatrade_referral'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
    engine::decide($decide, $action, $reason, (int) $USER->id);
    redirect($url, get_string('decisionsaved', 'local_alphatrade_referral'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$referredfields = \core_user\fields::for_name()->get_sql('f', false, '', 'ref', false)->selects;

/**
 * Nom affichable à partir des champs préfixés d'une requête.
 *
 * @param stdClass $row
 * @param string $prefix
 * @return string
 */
function local_atref_name(stdClass $row, string $prefix = ''): string {
    $user = new stdClass();
    foreach (\core_user\fields::get_name_fields() as $field) {
        $user->$field = $row->{$prefix . $field} ?? '';
    }
    return fullname($user);
}

// File de revue : ce qui attend une décision humaine.
$sql = "SELECT c.*, $userfields, $referredfields
          FROM {local_atref_conversions} c
          JOIN {user} u ON u.id = c.referrerid
          JOIN {user} f ON f.id = c.referredid
         WHERE c.status = :status
      ORDER BY c.score DESC, c.timecreated ASC";
$review = [];
foreach ($DB->get_records_sql($sql, ['status' => 'under_review'], 0, 100) as $row) {
    $signals = json_decode((string) $row->signals, true) ?: [];
    $review[] = [
        'id' => $row->id,
        'referrer' => local_atref_name($row),
        'referred' => local_atref_name($row, 'ref'),
        'amount' => $row->amount === null ? '-' : format_float($row->amount, 2) . ' ' . $row->currency,
        'signals' => implode(', ', $signals),
        'score' => $row->score,
        'date' => userdate($row->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
    ];
}

// Vue d'ensemble : les derniers parrainages, tous états confondus.
$sql = "SELECT r.id, r.status, r.timecreated, $userfields, $referredfields,
               (SELECT COUNT(1) FROM {local_atref_conversions} c WHERE c.relationid = r.id) AS conversions
          FROM {local_atref_relations} r
          JOIN {user} u ON u.id = r.referrerid
          JOIN {user} f ON f.id = r.referredid
      ORDER BY r.timecreated DESC";
$relations = [];
foreach ($DB->get_records_sql($sql, [], 0, 100) as $row) {
    $relations[] = [
        'referrer' => local_atref_name($row),
        'referred' => local_atref_name($row, 'ref'),
        'status' => get_string('relation_' . $row->status, 'local_alphatrade_referral'),
        'conversions' => (int) $row->conversions,
        'date' => userdate($row->timecreated, get_string('strftimedatefullshort', 'core_langconfig')),
    ];
}

$data = [
    'kpis' => [
        ['label' => get_string('kpi_codes', 'local_alphatrade_referral'),
            'value' => $DB->count_records('local_atref_codes', ['status' => 'active'])],
        ['label' => get_string('kpi_relations', 'local_alphatrade_referral'),
            'value' => $DB->count_records('local_atref_relations')],
        ['label' => get_string('kpi_approved', 'local_alphatrade_referral'),
            'value' => $DB->count_records('local_atref_conversions', ['status' => 'approved'])],
        ['label' => get_string('kpi_review', 'local_alphatrade_referral'),
            'value' => $DB->count_records('local_atref_conversions', ['status' => 'under_review'])],
    ],
    'review' => $review,
    'hasreview' => !empty($review),
    'relations' => $relations,
    'hasrelations' => !empty($relations),
    'action' => $url->out(false),
    'sesskey' => sesskey(),
    'canvalidate' => has_capability('local/alphatrade_referral:validate', $context),
    'queuefailed' => $DB->count_records('local_atref_queue', ['status' => 'failed']),
    'queuepending' => $DB->count_records('local_atref_queue', ['status' => 'pending']),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade_referral/manage', $data);
echo $OUTPUT->footer();
