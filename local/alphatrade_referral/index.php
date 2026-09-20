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
 * Tableau de bord du parrain : son lien, ses chiffres, ses filleuls, ses récompenses.
 * Chacun ne voit que son propre parrainage (§ 10.1).
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade_referral\local\discord;
use local_alphatrade_referral\local\engine;

require_login();
$context = context_system::instance();
require_capability('local/alphatrade_referral:viewown', $context);

if (!engine::enabled()) {
    throw new moodle_exception('disabled', 'local_alphatrade_referral');
}

// L'application Alpha Trade habille la page comme ses autres écrans ; sinon, page Moodle standard.
if (class_exists('\local_alphatrade\local\page')) {
    \local_alphatrade\local\page::setup('/local/alphatrade_referral/index.php', 'referral',
        get_string('dashboard', 'local_alphatrade_referral'), [],
        get_string('dashboard_sub', 'local_alphatrade_referral'));
} else {
    $PAGE->set_url('/local/alphatrade_referral/index.php');
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('dashboard', 'local_alphatrade_referral'));
    $PAGE->set_heading(get_string('dashboard', 'local_alphatrade_referral'));
}

$stats = engine::stats($USER->id);
$account = discord::account($USER->id);
$data = [
    'link' => $stats['link'],
    'code' => $stats['code'],
    'kpis' => [
        ['label' => get_string('kpi_clicks', 'local_alphatrade_referral'), 'value' => $stats['clicks']],
        ['label' => get_string('kpi_referrals', 'local_alphatrade_referral'), 'value' => $stats['referrals']],
        ['label' => get_string('kpi_conversions', 'local_alphatrade_referral'), 'value' => $stats['conversions']],
        ['label' => get_string('kpi_earned', 'local_alphatrade_referral'),
            'value' => format_float($stats['earned'], 2) . ' ' . $stats['currency']],
    ],
    'pending' => format_float($stats['pending'], 2) . ' ' . $stats['currency'],
    'haspending' => $stats['pending'] > 0,
    'validationdays' => (int) engine::config('validationdays', '14'),
    'referrals' => engine::referrals($USER->id),
    'rewards' => engine::rewards($USER->id),
    'hasdiscord' => discord::configured(),
    'islinked' => (bool) $account,
    'discordname' => $account ? $account->discordname : '',
    'linkurl' => (new moodle_url('/local/alphatrade_referral/discord.php', ['action' => 'link']))->out(false),
    'unlinkurl' => (new moodle_url('/local/alphatrade_referral/discord.php',
        ['action' => 'unlink', 'sesskey' => sesskey()]))->out(false),
];
$data['hasreferrals'] = !empty($data['referrals']);
$data['hasrewards'] = !empty($data['rewards']);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade_referral/dashboard', $data);
echo $OUTPUT->footer();
