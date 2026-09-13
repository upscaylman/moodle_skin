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
 * Messaging (maquette "Alpha Trade - Messagerie"): conversations, thread, reply, notifications.
 * Built on the core_message API: permissions, privacy and blocking rules of Moodle apply.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core_message\api;
use local_alphatrade\local\page;

$tab = optional_param('tab', 'messages', PARAM_ALPHA) === 'notifications' ? 'notifications' : 'messages';
$convid = optional_param('id', 0, PARAM_INT);
$touserid = optional_param('userid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$baseurl = new moodle_url('/local/alphatrade/messages.php');
$unreadnotifications = 0;
$links = [];

// Header links are built before setup so the standalone header can show them.
require_login(null, false);
if (isguestuser()) {
    redirect(get_login_url());
}
if (empty($CFG->messaging)) {
    throw new moodle_exception('disabled', 'message');
}
$unreadnotifications = \message_popup\api::count_unread_popup_notifications($USER->id);
$links = [
    ['url' => $baseurl->out(false), 'icon' => 'ph-chat-circle-text', 'label' => get_string('messages', 'message'),
        'active' => $tab === 'messages', 'badge' => ''],
    ['url' => (new moodle_url($baseurl, ['tab' => 'notifications']))->out(false), 'icon' => 'ph-bell',
        'label' => get_string('notifications', 'message'), 'active' => $tab === 'notifications',
        'badge' => $unreadnotifications ?: ''],
];
page::setup('/local/alphatrade/messages.php', 'messages', get_string('messaging', 'local_alphatrade'),
    array_filter(['tab' => $tab === 'notifications' ? $tab : '', 'id' => $convid]), '',
    ['standalone' => ['ismessaging' => true, 'links' => $links]]);
$PAGE->add_body_class('alpha-messaging');

// Notifications.
if ($tab === 'notifications') {
    if ($action === 'readall' && confirm_sesskey()) {
        api::mark_all_notifications_as_read($USER->id);
        redirect(new moodle_url($baseurl, ['tab' => 'notifications']));
    }
    $rows = [];
    foreach (\message_popup\api::get_popup_notifications($USER->id, 'DESC', 50, 0) as $notification) {
        $today = usergetmidnight(time());
        $time = (int) $notification->timecreated;
        if ($time >= $today) {
            $when = page::ago($time);
        } else if ($time >= $today - DAYSECS) {
            $when = get_string('yesterday_at', 'local_alphatrade', userdate($time, '%H:%M'));
        } else {
            $when = userdate($time, $time >= $today - 6 * DAYSECS ? '%A, %H:%M' : '%d %B');
        }
        $rows[] = [
            'text' => $notification->smallmessage !== '' && $notification->smallmessage !== null
                ? $notification->smallmessage : $notification->subject,
            'when' => core_text::strtotitle($when),
            'url' => !empty($notification->contexturl) ? (new moodle_url($notification->contexturl))->out(false) : '',
            'isunread' => empty($notification->timeread),
        ];
    }
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_alphatrade/notifications', [
        'rows' => $rows,
        'hasrows' => !empty($rows),
        'hasunread' => $unreadnotifications > 0,
        'readallurl' => (new moodle_url($baseurl, ['tab' => 'notifications', 'action' => 'readall',
            'sesskey' => sesskey()]))->out(false),
    ]);
    echo $OUTPUT->footer();
    exit;
}

// Open (or create) the conversation with a user: /local/alphatrade/messages.php?userid=X.
if ($touserid && $touserid != $USER->id) {
    $existing = api::get_conversation_between_users([$USER->id, $touserid]);
    if ($existing) {
        redirect(new moodle_url($baseurl, ['id' => $existing]));
    }
    if (!api::can_send_message($touserid, $USER->id)) {
        throw new moodle_exception('cannotsendmessage', 'local_alphatrade');
    }
    $conversation = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, [$USER->id, $touserid]);
    redirect(new moodle_url($baseurl, ['id' => $conversation->id]));
}

// Send a reply.
if ($action === 'send' && $convid && data_submitted()) {
    require_sesskey();
    $text = trim(optional_param('text', '', PARAM_RAW));
    if ($text !== '') {
        if (!api::can_send_message_to_conversation($USER->id, $convid)) {
            throw new moodle_exception('cannotsendmessage', 'local_alphatrade');
        }
        api::send_message_to_conversation($USER->id, $convid, core_text::substr($text, 0, 4000), FORMAT_MOODLE);
    }
    redirect(new moodle_url($baseurl, ['id' => $convid], 'at-msg-end'));
}

/**
 * Initials from a display name.
 *
 * @param string $name
 * @return string
 */
function local_alphatrade_name_initials(string $name): string {
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= core_text::substr($word, 0, 1);
    }
    return core_text::strtoupper($initials) ?: '?';
}

/**
 * Short time of the conversation list: 10:42, Hier, Lun., 12/09.
 *
 * @param int $time
 * @return string
 */
function local_alphatrade_short_time(int $time): string {
    $today = usergetmidnight(time());
    if ($time >= $today) {
        return userdate($time, '%H:%M');
    }
    if ($time >= $today - DAYSECS) {
        return get_string('ago_yesterday', 'local_alphatrade');
    }
    if ($time >= $today - 6 * DAYSECS) {
        return core_text::strtotitle(rtrim(userdate($time, '%a'), '.')) . '.';
    }
    return userdate($time, '%d/%m');
}

$conversations = api::get_conversations($USER->id, 0, 50);
$current = null;
$list = [];
foreach ($conversations as $conversation) {
    $name = $conversation->name;
    if ($conversation->type == api::MESSAGE_CONVERSATION_TYPE_SELF) {
        $name = fullname($USER);
    } else if ($name === '' || $name === null) {
        $name = $conversation->members ? reset($conversation->members)->fullname : '';
    }
    $last = $conversation->messages ? reset($conversation->messages) : null;
    $preview = $last ? shorten_text(trim(html_to_text($last->text, 0, false)), 80) : '';
    if ($last && $conversation->type == api::MESSAGE_CONVERSATION_TYPE_GROUP && $last->useridfrom != $USER->id) {
        foreach ($conversation->members as $member) {
            if ($member->id == $last->useridfrom) {
                $preview = get_string('preview_from', 'local_alphatrade', ['name' => $member->fullname, 'text' => $preview]);
            }
        }
    }
    $isactive = $convid ? $conversation->id == $convid : !$current;
    $item = [
        'id' => $conversation->id,
        'name' => $name,
        'initials' => local_alphatrade_name_initials($name),
        'preview' => $preview,
        'time' => $last ? local_alphatrade_short_time((int) $last->timecreated) : '',
        'url' => (new moodle_url($baseurl, ['id' => $conversation->id]))->out(false),
        'active' => $isactive,
        'unread' => $conversation->unreadcount ?: '',
    ];
    $list[] = $item;
    if ($isactive) {
        $current = ['conversation' => $conversation, 'item' => $item];
    }
}

// A conversation passed by id that is not in the first page of the list.
if ($convid && !$current && api::is_user_in_conversation($USER->id, $convid)) {
    $conversation = api::get_conversation($USER->id, $convid);
    $name = $conversation->name ?: ($conversation->members ? reset($conversation->members)->fullname : '');
    $current = ['conversation' => $conversation, 'item' => ['id' => $convid, 'name' => $name,
        'initials' => local_alphatrade_name_initials($name)]];
}

$thread = null;
if ($current) {
    $conversation = $current['conversation'];
    $result = api::get_conversation_messages($USER->id, $conversation->id, 0, 100, 'timecreated DESC');
    $messages = [];
    foreach (array_reverse($result['messages']) as $message) {
        $messages[] = [
            'text' => $message->text,
            'ismine' => $message->useridfrom == $USER->id,
            'time' => userdate($message->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }
    api::mark_all_messages_as_read($USER->id, $conversation->id);

    $subtitle = '';
    if ($conversation->type == api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL && $conversation->members) {
        $other = reset($conversation->members);
        $subtitle = has_capability('local/alphatrade:viewstudentdata', page::programme_context(), $other->id)
            ? get_string('role_teacher', 'local_alphatrade') : get_string('role_student', 'local_alphatrade');
    } else if (!empty($conversation->subname)) {
        $subtitle = $conversation->subname;
    }
    $thread = [
        'name' => $current['item']['name'],
        'initials' => $current['item']['initials'],
        'subtitle' => $subtitle,
        'messages' => $messages,
        'hasmessages' => !empty($messages),
        'cansend' => api::can_send_message_to_conversation($USER->id, $conversation->id),
        'action' => (new moodle_url($baseurl, ['id' => $conversation->id, 'action' => 'send']))->out(false),
        'sesskey' => sesskey(),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/messages', [
    'list' => $list,
    'haslist' => !empty($list),
    'thread' => $thread,
    'hasthread' => (bool) $thread,
    'showthread' => (bool) $convid,
    'backurl' => $baseurl->out(false),
]);
echo $OUTPUT->footer();
