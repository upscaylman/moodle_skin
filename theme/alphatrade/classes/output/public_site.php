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

namespace theme_alphatrade\output;

use moodle_url;

/**
 * Public site content (visitors): home, programme, method, trainers, FAQ, apply.
 * Copy lives in the language files so it can be edited with the language customisation tool.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class public_site {

    /**
     * Template data.
     *
     * @return array
     */
    public function export(): array {
        $str = function(string $key, $a = null): string {
            return get_string($key, 'theme_alphatrade', $a);
        };

        $applyurl = get_config('theme_alphatrade', 'applyurl');
        $contactemail = get_config('theme_alphatrade', 'contactemail');
        if (!$applyurl && $contactemail) {
            $applyurl = 'mailto:' . $contactemail;
        }
        if (!$applyurl && !empty(get_config('core', 'registerauth'))) {
            $applyurl = (new moodle_url('/login/signup.php'))->out(false);
        }

        $links = [];
        foreach (['programme', 'method', 'team', 'faq'] as $key) {
            $links[] = ['label' => $str('pub_nav_' . $key), 'anchor' => '#' . $key];
        }

        $facts = [];
        foreach (['weeks' => '12', 'hours' => '96 h', 'modules' => '7', 'trainers' => '2'] as $key => $value) {
            $facts[] = ['value' => $value, 'label' => $str('pub_fact_' . $key)];
        }

        $pillars = [];
        foreach (['theory' => 'ph-bank', 'psychology' => 'ph-brain', 'quant' => 'ph-chart-bar'] as $key => $icon) {
            $pillars[] = ['icon' => $icon, 'title' => $str('pub_pillar_' . $key), 'text' => $str('pub_pillar_' . $key . '_desc')];
        }

        $months = [];
        $weeks = [
            1 => ['1-2' => 'pub_w1', '3' => 'pub_w3', '4' => 'pub_w4'],
            2 => ['5' => 'pub_w5', '6' => 'pub_w6', '7' => 'pub_w7', '8' => 'pub_w8'],
            3 => ['9' => 'pub_w9', '10' => 'pub_w10', '11' => 'pub_w11', '12' => 'pub_w12'],
        ];
        foreach ($weeks as $month => $items) {
            $list = [];
            foreach ($items as $week => $key) {
                $list[] = ['week' => 'S' . $week, 'label' => $str($key)];
            }
            $months[] = [
                'label' => $str('pub_month', $month),
                'theme' => $str('pub_month' . $month . '_theme'),
                'weeks' => $list,
            ];
        }

        $method = [];
        foreach (['understand', 'analyse', 'test', 'measure', 'build'] as $key) {
            $method[] = ['title' => $str('pub_method_' . $key), 'text' => $str('pub_method_' . $key . '_desc')];
        }

        $team = [];
        foreach (['nesrine' => 'NM', 'julien' => 'JB'] as $key => $initials) {
            $team[] = [
                'initials' => $initials,
                'name' => $str('pub_team_' . $key),
                'role' => $str('pub_team_' . $key . '_role'),
                'bio' => $str('pub_team_' . $key . '_bio'),
                'quote' => $str('pub_team_' . $key . '_quote'),
            ];
        }

        $faq = [];
        foreach (['who', 'capital', 'signals', 'rhythm', 'certificate', 'online'] as $key) {
            $faq[] = ['question' => $str('pub_faq_' . $key), 'answer' => $str('pub_faq_' . $key . '_answer')];
        }

        return [
            'links' => $links,
            'facts' => $facts,
            'pillars' => $pillars,
            'months' => $months,
            'method' => $method,
            'team' => $team,
            'faq' => $faq,
            'applyurl' => $applyurl,
            'hasapply' => !empty($applyurl),
            'contactemail' => $contactemail,
            'year' => date('Y'),
        ];
    }
}
