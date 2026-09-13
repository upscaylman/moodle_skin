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
 * Trading journal (maquette Portail Etudiant, screen "Journal").
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\journal;
use local_alphatrade\local\page;

$assetclass = optional_param('class', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

page::setup('/local/alphatrade/journal.php', 'journal', get_string('journal_title', 'local_alphatrade'),
    array_filter(['class' => $assetclass, 'userid' => $userid]), get_string('sub_journal', 'local_alphatrade'));

$userid = $userid ?: $USER->id;
$isown = $userid == $USER->id;
if (!$isown) {
    require_capability('local/alphatrade:viewstudentdata', page::programme_context());
    page::set_header(fullname(core_user::get_user($userid)), get_string('sub_journal', 'local_alphatrade'));
}

$where = 'userid = :userid';
$params = ['userid' => $userid];
if (in_array($assetclass, journal::ASSETCLASSES)) {
    $where .= ' AND assetclass = :assetclass';
    $params['assetclass'] = $assetclass;
} else {
    $assetclass = '';
}
$entries = $DB->get_records_select('local_alphatrade_journal', $where, $params, 'tradedate DESC, id DESC', '*', 0, 300);
$rows = array_values(array_map([journal::class, 'export_row'], $entries));

// Weekly summary.
$weekstart = usergetmidnight(strtotime('monday this week'));
$week = $DB->get_records_select('local_alphatrade_journal', 'userid = :userid AND tradedate >= :since',
    ['userid' => $userid, 'since' => $weekstart], '', 'id, resultr');
$results = array_values(array_filter(array_map(function($entry) {
    return $entry->resultr === null ? null : (float) $entry->resultr;
}, $week), function($value) {
    return $value !== null;
}));
$wins = count(array_filter($results, function($value) {
    return $value > 0;
}));

$baseurl = new moodle_url('/local/alphatrade/journal.php', $isown ? [] : ['userid' => $userid]);
$tags = [['label' => get_string('all_f', 'local_alphatrade'), 'url' => $baseurl->out(false), 'active' => $assetclass === '']];
foreach (['forex', 'indices', 'crypto', 'gold'] as $key) {
    $tags[] = [
        'label' => get_string('assetclass_' . $key, 'local_alphatrade'),
        'url' => (new moodle_url($baseurl, ['class' => $key]))->out(false),
        'active' => $assetclass === $key,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/journal', [
    'isown' => $isown,
    'addurl' => (new moodle_url('/local/alphatrade/journalentry.php', ['edit' => 1]))->out(false),
    'tags' => $tags,
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'week' => get_string('weeksummary', 'local_alphatrade', [
        'trades' => count($week),
        'r' => page::format_r(array_sum($results), 1),
        'winrate' => $results ? (int) round(100 * $wins / count($results)) : 0,
    ]),
]);
echo $OUTPUT->footer();
