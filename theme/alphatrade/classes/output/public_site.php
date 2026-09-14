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
 * Public site (maquette "Alpha Trade - Site Public"): home, programme, method, trainers, FAQ, apply.
 * Copy lives in the language files so it can be edited with the language customisation tool.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class public_site {

    /** @var string[] Views of the public site. */
    const VIEWS = ['accueil', 'programme', 'methode', 'formateurs', 'faq', 'candidater'];

    /** @var string Programme brochure served from theme/alphatrade/files (download buttons). */
    const PROGRAMME_PDF = 'Alpha-Trade-Programme-de-Formation-Trading-et-Finance.pdf';

    /** @var string Current view. */
    protected $view;

    /**
     * Constructor.
     *
     * @param string $view
     */
    public function __construct(string $view) {
        $this->view = in_array($view, self::VIEWS) ? $view : 'accueil';
    }

    /**
     * URL of a view.
     *
     * @param string $view
     * @return string
     */
    public static function url(string $view): string {
        return (new moodle_url('/', $view === 'accueil' ? [] : ['view' => $view]))->out(false);
    }

    /**
     * Template data.
     *
     * @return array
     */
    public function export(): array {
        global $OUTPUT;

        $str = function(string $key, $a = null): string {
            return get_string($key, 'theme_alphatrade', $a);
        };

        $nav = [];
        foreach (['accueil', 'programme', 'methode', 'formateurs', 'faq'] as $view) {
            $nav[] = ['label' => $str('pub_nav_' . $view), 'url' => self::url($view), 'active' => $view === $this->view];
        }

        $pipeline = [];
        $steps = ['understand' => 'ph-book-open', 'analyse' => 'ph-magnifying-glass', 'test' => 'ph-flask',
            'measure' => 'ph-chart-bar', 'build' => 'ph-hammer'];
        foreach ($steps as $key => $icon) {
            $pipeline[] = ['label' => $str('pub_pipe_' . $key), 'icon' => $icon, 'hasarrow' => $key !== 'build'];
        }

        $months = [];
        $number = 0;
        foreach ([1 => 3, 2 => 4, 3 => 5] as $month => $count) {
            $modules = [];
            for ($i = 0; $i < $count; $i++) {
                $number++;
                $modules[] = [
                    'number' => sprintf('%02d', $number),
                    'title' => $str('pub_mod' . $number),
                    'desc' => $str('pub_mod' . $number . '_desc'),
                ];
            }
            $months[] = [
                'number' => $month,
                'label' => $str('pub_month' . $month . '_theme'),
                'summary' => $str('pub_month' . $month . '_summary'),
                'modules' => $modules,
            ];
        }

        $method = [];
        for ($i = 1; $i <= 8; $i++) {
            $method[] = ['number' => $i, 'title' => $str('pub_step' . $i), 'desc' => $str('pub_step' . $i . '_desc')];
        }

        $team = [];
        foreach (['nesrine' => 'NM', 'julien' => 'JB'] as $key => $initials) {
            $team[] = [
                'initials' => $initials,
                'name' => $str('pub_team_' . $key),
                'role' => $str('pub_team_' . $key . '_role'),
                'bio' => $str('pub_team_' . $key . '_bio'),
            ];
        }

        $faq = [];
        foreach (['signals', 'experience', 'duration', 'project', 'admission'] as $key) {
            $faq[] = ['question' => $str('pub_faq_' . $key), 'answer' => $str('pub_faq_' . $key . '_answer')];
        }

        // Application: the Alpha Trade form when the app plugin is installed, else a URL or an e-mail.
        $hasform = theme_alphatrade_has_app();
        $applyurl = get_config('theme_alphatrade', 'applyurl');
        $contactemail = get_config('theme_alphatrade', 'contactemail');
        if (!$applyurl && $contactemail) {
            $applyurl = 'mailto:' . $contactemail;
        }

        $data = [
            'nav' => $nav,
            'homeurl' => self::url('accueil'),
            'programmeurl' => self::url('programme'),
            'teamurl' => self::url('formateurs'),
            'candidaterurl' => $hasform || !$applyurl ? self::url('candidater') : $applyurl,
            'pipeline' => $pipeline,
            'months' => $months,
            'method' => $method,
            'team' => $team,
            'faq' => $faq,
            'hasform' => $hasform,
            'applyurl' => $applyurl,
            'hasapplyurl' => !empty($applyurl),
            'formaction' => $hasform ? (new moodle_url('/local/alphatrade/apply.php'))->out(false) : '',
            'sesskey' => sesskey(),
            'applied' => optional_param('applied', 0, PARAM_BOOL),
            'programmepdfurl' => (new moodle_url('/theme/alphatrade/files/' . self::PROGRAMME_PDF))->out(false),
        ];

        // Hero headline: two variants (kicker, title, subtitle) alternating every 5 s in the browser.
        $headlines = [];
        foreach (['', '2'] as $suffix) {
            $headline = [
                'kicker' => $str('pub_hero_tag' . $suffix),
                'title' => $str('pub_hero_title' . $suffix),
                'subtitle' => $str('pub_hero_lead' . $suffix),
            ];
            if (trim($headline['title']) !== '') {
                $headlines[] = $headline;
            }
        }
        $data['headline'] = $headlines[0];
        $data['headlinesjson'] = json_encode($headlines, JSON_UNESCAPED_UNICODE);

        // Hero carousel: pix/hero-slide-1, hero-slide-2 (jpg or png) when present, else the single hero image.
        $slides = [];
        foreach ([1, 2] as $number) {
            if (glob(__DIR__ . "/../../pix/hero-slide-$number.{jpg,png}", GLOB_BRACE)) {
                $slides[] = [
                    'url' => $OUTPUT->image_url("hero-slide-$number", 'theme_alphatrade')->out(false),
                    'alt' => $str('pub_hero_alt'),
                    'label' => $str('pub_slide', $number),
                    'index' => count($slides),
                    'first' => empty($slides),
                ];
            }
        }
        if (!$slides) {
            $slides[] = ['url' => $OUTPUT->image_url('hero', 'theme_alphatrade')->out(false), 'alt' => $str('pub_hero_alt'),
                'label' => $str('pub_slide', 1), 'index' => 0, 'first' => true];
        }
        $data['slides'] = $slides;
        $data['hasslides'] = count($slides) > 1;
        foreach (self::VIEWS as $view) {
            $data['is' . $view] = $view === $this->view;
        }
        return $data;
    }
}
