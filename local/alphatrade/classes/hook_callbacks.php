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

namespace local_alphatrade;

use context_course;
use moodle_url;

/**
 * Hook callbacks: Moodle stays the invisible engine, students land on Alpha Trade pages.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Redirect Moodle's messaging and the programme course page to their Alpha Trade equivalent.
     * The dashboard is not redirected: /my/ itself shows the Alpha Trade dashboard (theme layout).
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $DB, $CFG;

        if (during_initial_install() || CLI_SCRIPT || AJAX_SCRIPT || !isloggedin() || isguestuser() || !$PAGE->has_set_url()) {
            return;
        }

        $config = get_config('local_alphatrade');

        // Moodle messaging pages open the Alpha Trade messaging (maquette Messagerie).
        if (!empty($config->redirectmessages) && !empty($CFG->messaging)) {
            if ($PAGE->url->compare(new moodle_url('/message/index.php'), URL_MATCH_BASE)) {
                $convid = (int) $PAGE->url->get_param('convid');
                $userid = (int) $PAGE->url->get_param('id');
                redirect(new moodle_url('/local/alphatrade/messages.php', array_filter(['id' => $convid, 'userid' => $userid])));
            }
            if ($PAGE->url->compare(new moodle_url('/message/output/popup/notifications.php'), URL_MATCH_BASE)) {
                redirect(new moodle_url('/local/alphatrade/messages.php', ['tab' => 'notifications']));
            }
        }

        if ($PAGE->pagelayout !== 'course') {
            return;
        }

        $programmeid = (int) ($config->programmecourse ?? 0);
        if (empty($config->redirectcourse) || !$programmeid || $PAGE->course->id != $programmeid) {
            return;
        }
        if (has_capability('moodle/course:update', context_course::instance($programmeid))) {
            return;
        }

        if ($PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
            $section = (int) $PAGE->url->get_param('section');
            if ($section > 0) {
                redirect(new moodle_url('/local/alphatrade/module.php', ['section' => $section]));
            }
            redirect(new moodle_url('/local/alphatrade/parcours.php'));
        }
        if ($PAGE->url->compare(new moodle_url('/course/section.php'), URL_MATCH_BASE)) {
            $sectionid = (int) $PAGE->url->get_param('id');
            $section = $DB->get_field('course_sections', 'section', ['id' => $sectionid, 'course' => $programmeid]);
            if ($section) {
                redirect(new moodle_url('/local/alphatrade/module.php', ['section' => $section]));
            }
            redirect(new moodle_url('/local/alphatrade/parcours.php'));
        }
    }
}
