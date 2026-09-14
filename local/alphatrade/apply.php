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
 * Application form of the public site (maquette Site Public, view "Candidater").
 * Visitors are not logged in: sesskey, honeypot and per-IP throttling protect the endpoint.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/alphatrade/apply.php'));

$formurl = new moodle_url('/', ['view' => 'candidater']);
if (!data_submitted()) {
    redirect($formurl);
}
require_sesskey();

// Bots fill the hidden field: pretend it worked.
if (optional_param('website', '', PARAM_RAW_TRIMMED) !== '') {
    redirect(new moodle_url($formurl, ['applied' => 1]));
}

// At most 3 applications per hour from the same address.
$cache = cache::make('local_alphatrade', 'applications');
$key = sha1(getremoteaddr() . '|' . get_site_identifier());
$count = (int) $cache->get($key);
if ($count >= 3) {
    redirect($formurl, get_string('apply_throttled', 'local_alphatrade'), null, \core\output\notification::NOTIFY_ERROR);
}

$fullname = core_text::substr(trim(optional_param('fullname', '', PARAM_TEXT)), 0, 120);
$email = core_text::substr(trim(optional_param('email', '', PARAM_EMAIL)), 0, 120);
$phone = core_text::substr(trim(optional_param('phone', '', PARAM_TEXT)), 0, 40);
$motivation = core_text::substr(trim(optional_param('motivation', '', PARAM_TEXT)), 0, 500);
$level = optional_param('level', '', PARAM_ALPHA);
if (!in_array($level, ['beginner', 'intermediate', 'advanced'])) {
    $level = '';
}

if ($fullname === '' || $email === '' || !validate_email($email)) {
    redirect($formurl, get_string('apply_invalid', 'local_alphatrade'), null, \core\output\notification::NOTIFY_ERROR);
}

$cache->set($key, $count + 1);
$application = (object) [
    'fullname' => $fullname,
    'email' => $email,
    'phone' => $phone,
    'level' => $level,
    'motivation' => $motivation,
    'status' => 'new',
    'timecreated' => time(),
];
$application->id = $DB->insert_record('local_alphatrade_application', $application);

// Notify the site administrators.
foreach (get_admins() as $admin) {
    $message = new \core\message\message();
    $message->component = 'local_alphatrade';
    $message->name = 'newapplication';
    $message->userfrom = core_user::get_noreply_user();
    $message->userto = $admin;
    $message->subject = get_string('notification_application_subject', 'local_alphatrade', $fullname);
    $message->fullmessage = get_string('notification_application_body', 'local_alphatrade', [
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : '-',
        'level' => $level !== '' ? get_string('level_' . $level, 'local_alphatrade') : '-',
        'motivation' => $motivation !== '' ? $motivation : '-',
    ]);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '';
    $message->smallmessage = $message->subject;
    $message->notification = 1;
    $message->contexturl = (new moodle_url('/local/alphatrade/admin.php', ['view' => 'applications']))->out(false);
    $message->contexturlname = get_string('applications', 'local_alphatrade');
    message_send($message);
}

redirect(new moodle_url($formurl, ['applied' => 1]));
