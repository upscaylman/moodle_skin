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

namespace local_alphatrade\local;

use context_system;
use moodle_url;

/**
 * Alpha Trade dashboard (maquette Portail Etudiant, screen "Accueil"), shown on Moodle's own dashboard /my/
 * above the Timeline and Calendar blocks: there is a single dashboard page.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard {

    /** @var float Périmètre de l'anneau de progression : 2 x pi x 30, arrondi comme dans la maquette. */
    const RING_LENGTH = 188.5;

    /**
     * Les échéances qui attendent l'étudiant, depuis les événements d'action de Moodle :
     * les mêmes données que le bloc Chronologie, rendues dans la carte de la maquette.
     *
     * @param \stdClass $user
     * @param int $limit
     * @return array
     */
    public static function deadlines(\stdClass $user, int $limit = 6): array {
        global $USER;

        if ((int) $user->id !== (int) $USER->id) {
            // Les événements d'action ne se lisent que pour l'utilisateur courant.
            return [];
        }
        $icons = ['quiz' => 'ph-exam', 'assign' => 'ph-file-text', 'page' => 'ph-article',
            'forum' => 'ph-chats-circle', 'url' => 'ph-play-circle', 'resource' => 'ph-file-text',
            'choice' => 'ph-list-checks', 'feedback' => 'ph-chat-circle-text'];
        $rows = [];
        try {
            $events = \core_calendar\localpi::get_action_events_by_timesort(time(), null, null, $limit, true);
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($events as $event) {
            $cm = $event->get_course_module();
            $modname = $cm ? $cm->get('modname') : '';
            $course = $event->get_course();
            $rows[] = [
                'title' => format_string($event->get_name()),
                'module' => $course ? format_string($course->get('fullname')) : '',
                'due' => self::due_label((int) $event->get_times()->get_sort_time()->getTimestamp()),
                'icon' => $icons[$modname] ?? 'ph-calendar-blank',
                'url' => $event->get_action() ? $event->get_action()->get_url()->out(false) : '',
            ];
        }
        return $rows;
    }

    /**
     * L'échéance en clair : aujourd'hui, demain, dans N jours, sinon la date.
     *
     * @param int $time
     * @return string
     */
    protected static function due_label(int $time): string {
        $days = (int) floor(($time - time()) / DAYSECS);
        if ($days <= 0) {
            return get_string('due_today', 'local_alphatrade');
        }
        if ($days === 1) {
            return get_string('due_tomorrow', 'local_alphatrade');
        }
        if ($days <= 7) {
            return get_string('due_days', 'local_alphatrade', $days);
        }
        return userdate($time, get_string('strftimedatefullshort', 'core_langconfig'));
    }

    /**
     * Is the Alpha Trade dashboard shown on /my/ (setting "redirectdashboard")?
     *
     * @return bool
     */    /**
     * Is the Alpha Trade dashboard shown on /my/ (setting "redirectdashboard")?
     *
     * @return bool
     */
    public static function enabled(): bool {
        return (bool) get_config('local_alphatrade', 'redirectdashboard');
    }

    /**
     * Template data of local_alphatrade/dashboard.
     *
     * @param \stdClass $user
     * @return array
     */
    public static function export(\stdClass $user): array {
        $streak = activity::get_streak($user->id);
        $data = [
            'greeting' => get_string('greeting', 'local_alphatrade', format_string($user->firstname)),
            'hasprogramme' => false,
            'isadmin' => has_capability('moodle/site:config', context_system::instance()),
            'settingsurl' => (new moodle_url('/admin/settings.php', ['section' => 'local_alphatrade']))->out(false),
            'streak' => get_string('streakdays', 'local_alphatrade', $streak),
            'trades' => activity::count_backtested_trades($user->id),
            'badges' => activity::count_badges($user->id),
            'deadlines' => self::deadlines($user),
        ];
        $data['hasdeadlines'] = !empty($data['deadlines']);

        $programme = programme::for_user($user->id);
        if (!$programme) {
            return $data;
        }

        $summary = $programme->get_summary();
        $next = $programme->get_next();

        $data['hasprogramme'] = true;
        $data['percent'] = $summary['percent'];
        $data['week'] = get_string('weekcounter', 'local_alphatrade', ['week' => $summary['week'], 'weeks' => $summary['weeks']]);
        // Anneau de progression : périmètre d'un cercle de rayon 30, entamé d'autant que le pourcentage.
        $data['ringdash'] = self::RING_LENGTH;
        $data['ringoffset'] = round(self::RING_LENGTH * (1 - $summary['percent'] / 100), 1);
        $current = $programme->get_current_module();
        $data['currentmodule'] = $current ? programme::module_title($current) : '';

        if ($next) {
            $module = $next['module'];
            $item = $next['item'];
            $itemlabel = get_string('openmodule', 'local_alphatrade');
            if ($item) {
                $number = '';
                foreach ($module['lessons'] as $lesson) {
                    if ($lesson['cmid'] == $item['cmid']) {
                        $number = $lesson['number'];
                    }
                }
                $itemlabel = $item['isquiz'] ? get_string('evaluation', 'local_alphatrade') . ' - ' . $item['name']
                    : get_string('lessonnumbered', 'local_alphatrade', ['number' => $number, 'name' => $item['name']]);
            }
            $data['continue'] = [
                'title' => programme::module_title($module),
                'item' => $itemlabel,
                'url' => $item ? $item['url'] : $module['url'],
                'cta' => $item && $item['isquiz'] ? get_string('startevaluation', 'local_alphatrade')
                    : get_string('continuelesson', 'local_alphatrade'),
            ];
        } else if ($summary['iscomplete']) {
            $data['continue'] = [
                'title' => get_string('programmedone', 'local_alphatrade'),
                'item' => get_string('programmedone_title', 'local_alphatrade'),
                'url' => (new moodle_url('/local/alphatrade/project.php'))->out(false),
                'cta' => get_string('finalproject', 'local_alphatrade'),
            ];
        }

        $data['modules'] = array_map(function($module) {
            return [
                'number' => $module['number'],
                'label' => $module['label'],
                'name' => $module['name'],
                'url' => $module['url'],
                'islocked' => $module['islocked'],
                'iconclass' => programme::maquette_icon($module['status']),
                'statusclass' => programme::maquette_status_class($module['status']),
                'statuslabel' => $module['statuslabel'],
            ];
        }, $programme->get_modules());

        return $data;
    }
}
