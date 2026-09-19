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

    /**
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
        ];

        $programme = programme::for_user($user->id);
        if (!$programme) {
            return $data;
        }

        $summary = $programme->get_summary();
        $next = $programme->get_next();

        $data['hasprogramme'] = true;
        $data['percent'] = $summary['percent'];
        $data['week'] = get_string('weekcounter', 'local_alphatrade', ['week' => $summary['week'], 'weeks' => $summary['weeks']]);

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
