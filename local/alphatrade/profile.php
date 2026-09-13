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
 * Profile & progress: training %, figures, badges, certificate, settings.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

use local_alphatrade\local\activity;
use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/profile.php', 'profile', get_string('profile', 'local_alphatrade'));

$data = [
    'fullname' => fullname($USER),
    'avatar' => $OUTPUT->user_picture($USER, ['size' => 128, 'link' => false, 'class' => 'alpha-avatar']),
    'email' => $USER->email,
    'hasprogramme' => false,
    'trades' => activity::count_backtested_trades($USER->id),
    'journal' => $DB->count_records('local_alphatrade_journal', ['userid' => $USER->id]),
    'analyses' => $DB->count_records('local_alphatrade_analysis', ['userid' => $USER->id]),
    'certificateurl' => (new moodle_url('/local/alphatrade/certificate.php'))->out(false),
    'projecturl' => (new moodle_url('/local/alphatrade/project.php'))->out(false),
    'links' => [
        ['icon' => 'ph-user-circle', 'label' => get_string('editmyprofile'),
            'url' => (new moodle_url('/user/edit.php'))->out(false)],
        ['icon' => 'ph-sliders-horizontal', 'label' => get_string('preferences'),
            'url' => (new moodle_url('/user/preferences.php'))->out(false)],
        ['icon' => 'ph-bell', 'label' => get_string('notificationpreferences', 'message'),
            'url' => (new moodle_url('/message/notificationpreferences.php'))->out(false)],
        ['icon' => 'ph-key', 'label' => get_string('changepassword'),
            'url' => (new moodle_url('/login/change_password.php'))->out(false)],
        ['icon' => 'ph-sign-out', 'label' => get_string('logout'),
            'url' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false)],
    ],
];

// Challenges done / total (practice course, challenges section).
$challengesdone = 0;
$challengestotal = 0;
$practiceid = (int) get_config('local_alphatrade', 'practicecourse');
if ($practiceid && $DB->record_exists('course', ['id' => $practiceid])) {
    $practice = get_course($practiceid);
    $modinfo = get_fast_modinfo($practice, $USER->id);
    $completion = new completion_info($practice);
    foreach ($modinfo->sections[(int) get_config('local_alphatrade', 'challengessection')] ?? [] as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if (!$cm->url || $completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
            continue;
        }
        $challengestotal++;
        $state = $completion->get_data($cm, false, $USER->id)->completionstate;
        $challengesdone += in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]) ? 1 : 0;
    }
}

$programme = programme::for_user($USER->id);
if ($programme) {
    $summary = $programme->get_summary();
    $data['hasprogramme'] = true;
    $data['percent'] = $summary['percent'];
    $data['figures'] = [
        ['icon' => 'ph-stack', 'label' => get_string('modules', 'local_alphatrade'),
            'value' => $summary['modulesdone'] . ' / ' . $summary['modulestotal']],
        ['icon' => 'ph-article', 'label' => get_string('lessons', 'local_alphatrade'),
            'value' => $summary['lessonsdone'] . ' / ' . $summary['lessonstotal']],
        ['icon' => 'ph-exam', 'label' => get_string('quizzes', 'local_alphatrade'),
            'value' => $summary['quizzesdone'] . ' / ' . $summary['quizzestotal']],
        ['icon' => 'ph-trophy', 'label' => get_string('practice_challenges', 'local_alphatrade'),
            'value' => $challengesdone . ' / ' . $challengestotal],
        ['icon' => 'ph-flask', 'label' => get_string('backtestedtrades', 'local_alphatrade'),
            'value' => $data['trades']],
        ['icon' => 'ph-notebook', 'label' => get_string('journalentries', 'local_alphatrade'),
            'value' => $data['journal']],
    ];

    // Badges of the programme course: earned or locked.
    $data['badges'] = [];
    if (!empty($CFG->enablebadges)) {
        require_once($CFG->libdir . '/badgeslib.php');
        $coursebadges = badges_get_badges(BADGE_TYPE_COURSE, $programme->get_course_record()->id, '', '', 0, 0);
        foreach ($coursebadges as $badge) {
            if (!$badge->is_active()) {
                continue;
            }
            $earned = $badge->is_issued($USER->id);
            $data['badges'][] = [
                'name' => format_string($badge->name),
                'image' => moodle_url::make_pluginfile_url($badge->get_context()->id, 'badges', 'badgeimage',
                    $badge->id, '/', 'f3', false)->out(false),
                'earned' => $earned,
            ];
        }
    }
    $data['hasbadges'] = !empty($data['badges']);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/profile', $data);
echo $OUTPUT->footer();
