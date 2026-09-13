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
 * Community (wireframe v3 screen 13): three spaces only, no trading signals.
 * Announcements = news forum of the programme course; Q&A and market analyses = configured forums.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\programme;

page::setup('/local/alphatrade/community.php', page::is_staff() ? 'teachercommunity' : 'community', get_string('community', 'local_alphatrade'), [],
    get_string('sub_community', 'local_alphatrade'));

/**
 * Forum card data for a course module id (null when missing or not visible).
 *
 * @param int $cmid
 * @return array|null
 */
function local_alphatrade_forum_card(int $cmid): ?array {
    global $DB, $USER;
    if (!$cmid) {
        return null;
    }
    $cmrecord = $DB->get_record('course_modules', ['id' => $cmid]);
    if (!$cmrecord) {
        return null;
    }
    $cm = get_fast_modinfo($cmrecord->course, $USER->id)->get_cm($cmid);
    if (!$cm->uservisible || $cm->modname !== 'forum') {
        return null;
    }
    $discussions = $DB->count_records('forum_discussions', ['forum' => $cm->instance]);
    return [
        'forumid' => (int) $cm->instance,
        'forumname' => $cm->get_formatted_name(),
        'url' => $cm->url->out(false),
        'meta' => get_string('discussioncount', 'local_alphatrade', $discussions),
    ];
}

$cards = [];

$programmecourse = programme::get_course();
if ($programmecourse) {
    $news = $DB->get_record('forum', ['course' => $programmecourse->id, 'type' => 'news'], 'id', IGNORE_MULTIPLE);
    if ($news) {
        $cm = get_coursemodule_from_instance('forum', $news->id, $programmecourse->id);
        $card = $cm ? local_alphatrade_forum_card((int) $cm->id) : null;
        if ($card) {
            $cards[] = array_merge($card, [
                'icon' => 'ph-megaphone',
                'title' => get_string('community_announcements', 'local_alphatrade'),
                'text' => get_string('community_announcements_desc', 'local_alphatrade'),
            ]);
        }
    }
}

$config = get_config('local_alphatrade');
$spaces = [
    'qaforum' => ['icon' => 'ph-chat-circle-text', 'key' => 'community_qa'],
    'analysisforum' => ['icon' => 'ph-chart-line-up', 'key' => 'community_analyses'],
];
foreach ($spaces as $setting => $space) {
    $card = local_alphatrade_forum_card((int) ($config->$setting ?? 0));
    if ($card) {
        $cards[] = array_merge($card, [
            'icon' => $space['icon'],
            'title' => get_string($space['key'], 'local_alphatrade'),
            'text' => get_string($space['key'] . '_desc', 'local_alphatrade'),
        ]);
    }
}

// Trainers: latest posts to answer or moderate (maquette Espace Enseignant, screen "Communauté").
$posts = [];
if (page::is_staff() && $cards) {
    $forums = array_column($cards, 'forumname', 'forumid');
    list($insql, $params) = $DB->get_in_or_equal(array_keys($forums), SQL_PARAMS_NAMED, 'f');
    $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
    $sql = "SELECT p.id, p.discussion, p.message, p.messageformat, p.created, d.forum, u.id AS userid, $userfields
              FROM {forum_posts} p
              JOIN {forum_discussions} d ON d.id = p.discussion
              JOIN {user} u ON u.id = p.userid
             WHERE d.forum $insql AND p.deleted = 0
          ORDER BY p.created DESC";
    foreach ($DB->get_records_sql($sql, $params, 0, 10) as $post) {
        $text = trim(html_to_text(format_text($post->message, $post->messageformat), 0, false));
        $posts[] = [
            'initials' => page::initials($post),
            'author' => fullname($post),
            'forum' => $forums[$post->forum] ?? '',
            'excerpt' => shorten_text($text, 220),
            'time' => page::ago((int) $post->created),
            'url' => (new moodle_url('/mod/forum/discuss.php', ['d' => $post->discussion], 'p' . $post->id))->out(false),
        ];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/community', [
    'cards' => $cards,
    'hascards' => !empty($cards),
    'posts' => $posts,
    'hasposts' => !empty($posts),
]);
echo $OUTPUT->footer();
