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
 * Trading journal (wireframe v3 screen 10): chronological list grouped by day, filters, weekly summary.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\journal;
use local_alphatrade\local\page;

$assetclass = optional_param('class', '', PARAM_ALPHA);
$result = optional_param('result', '', PARAM_ALPHA);
$emotion = optional_param('emotion', '', PARAM_ALPHA);
$setup = optional_param('setup', '', PARAM_TEXT);
$period = optional_param('period', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

$params = array_filter(['class' => $assetclass, 'result' => $result, 'emotion' => $emotion, 'setup' => $setup,
    'period' => $period, 'userid' => $userid]);
page::setup('/local/alphatrade/journal.php', 'journal', get_string('journal', 'local_alphatrade'), $params);

$userid = $userid ?: $USER->id;
$isown = $userid == $USER->id;
if (!$isown) {
    require_capability('local/alphatrade:viewstudentdata', page::programme_context());
}

$where = ['userid = :userid'];
$sqlparams = ['userid' => $userid];
if (in_array($assetclass, journal::ASSETCLASSES)) {
    $where[] = 'assetclass = :assetclass';
    $sqlparams['assetclass'] = $assetclass;
}
if ($result === 'win') {
    $where[] = 'resultr > 0';
} else if ($result === 'loss') {
    $where[] = 'resultr < 0';
}
if (isset(journal::EMOTIONS[$emotion])) {
    $where[] = 'emotion = :emotion';
    $sqlparams['emotion'] = $emotion;
}
if ($setup !== '') {
    $where[] = $DB->sql_like('setup', ':setup', false, false);
    $sqlparams['setup'] = '%' . $DB->sql_like_escape($setup) . '%';
}
$weekstart = usergetmidnight(strtotime('monday this week', time()));
if ($period === 'week') {
    $where[] = 'tradedate >= :since';
    $sqlparams['since'] = $weekstart;
} else if ($period === 'month') {
    $where[] = 'tradedate >= :since';
    $sqlparams['since'] = usergetmidnight(strtotime('first day of this month', time()));
}

$entries = $DB->get_records_select('local_alphatrade_journal', implode(' AND ', $where), $sqlparams,
    'tradedate DESC, id DESC', '*', 0, 300);

$groups = [];
foreach ($entries as $entry) {
    $day = usergetmidnight($entry->tradedate);
    if (!isset($groups[$day])) {
        $groups[$day] = [
            'date' => userdate($day, get_string('strftimedaydate', 'langconfig')),
            'rows' => [],
        ];
    }
    $groups[$day]['rows'][] = journal::export_row($entry);
}

// Weekly summary: "Cette semaine - X trades · +YR · Z % win rate".
$week = $DB->get_records_select('local_alphatrade_journal', 'userid = :userid AND tradedate >= :since',
    ['userid' => $userid, 'since' => $weekstart], '', 'id, resultr');
$weekresults = array_filter(array_map(function($entry) {
    return $entry->resultr === null ? null : (float) $entry->resultr;
}, $week), function($value) {
    return $value !== null;
});
$weekwins = count(array_filter($weekresults, function($value) {
    return $value > 0;
}));

$baseurl = new moodle_url('/local/alphatrade/journal.php', $isown ? [] : ['userid' => $userid]);
$pills = [['label' => get_string('all', 'local_alphatrade'), 'url' => $baseurl->out(false), 'active' => $assetclass === '']];
foreach (['forex', 'indices', 'crypto', 'gold', 'stocks'] as $key) {
    $pills[] = [
        'label' => get_string('assetclass_' . $key, 'local_alphatrade'),
        'url' => (new moodle_url($baseurl, ['class' => $key]))->out(false),
        'active' => $assetclass === $key,
    ];
}

$select = function(array $options, string $current): array {
    $out = [];
    foreach ($options as $value => $label) {
        $out[] = ['value' => $value, 'label' => $label, 'selected' => (string) $value === $current];
    }
    return $out;
};

$owner = $isown ? null : core_user::get_user($userid);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/journal', [
    'isown' => $isown,
    'owner' => $owner ? fullname($owner) : '',
    'addurl' => (new moodle_url('/local/alphatrade/journalentry.php', ['edit' => 1]))->out(false),
    'pills' => $pills,
    'filteraction' => (new moodle_url('/local/alphatrade/journal.php'))->out(false),
    'hiddenparams' => array_values(array_filter([
        $assetclass ? ['name' => 'class', 'value' => $assetclass] : null,
        $isown ? null : ['name' => 'userid', 'value' => $userid],
    ])),
    'setup' => $setup,
    'periods' => $select(['' => get_string('period_all', 'local_alphatrade'), 'week' => get_string('period_week', 'local_alphatrade'),
        'month' => get_string('period_month', 'local_alphatrade')], $period),
    'results' => $select(['' => get_string('result_all', 'local_alphatrade'), 'win' => get_string('result_win', 'local_alphatrade'),
        'loss' => get_string('result_loss', 'local_alphatrade')], $result),
    'emotions' => $select(['' => get_string('emotion_all', 'local_alphatrade')] + journal::options('emotion'), $emotion),
    'hasfilters' => $result || $emotion || $setup !== '' || $period,
    'resetfiltersurl' => (new moodle_url($baseurl, $assetclass ? ['class' => $assetclass] : []))->out(false),
    'groups' => array_values($groups),
    'hasentries' => !empty($groups),
    'week' => get_string('weeksummary', 'local_alphatrade', [
        'trades' => count($week),
        'r' => page::format_r(array_sum($weekresults), 1),
        'winrate' => $weekresults ? (int) round(100 * $weekwins / count($weekresults)) : 0,
    ]),
]);
echo $OUTPUT->footer();
