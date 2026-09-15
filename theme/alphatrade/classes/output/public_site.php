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

    /** @var string[] Practical facts, in display order. */
    const FACTS = ['format', 'start', 'price', 'certificate'];

    /** @var string[] Course formats (schema.org courseMode values). */
    const FORMATS = ['online', 'onsite', 'blended'];

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
     * Current view.
     *
     * @return string
     */
    public function view(): string {
        return $this->view;
    }

    /**
     * Programme: 3 months, 12 modules.
     *
     * @return array
     */
    public static function months(): array {
        $months = [];
        $number = 0;
        foreach ([1 => 3, 2 => 4, 3 => 5] as $month => $count) {
            $modules = [];
            for ($i = 0; $i < $count; $i++) {
                $number++;
                $modules[] = [
                    'number' => sprintf('%02d', $number),
                    'title' => get_string('pub_mod' . $number, 'theme_alphatrade'),
                    'desc' => get_string('pub_mod' . $number . '_desc', 'theme_alphatrade'),
                ];
            }
            $months[] = [
                'number' => $month,
                'label' => get_string('pub_month' . $month . '_theme', 'theme_alphatrade'),
                'summary' => get_string('pub_month' . $month . '_summary', 'theme_alphatrade'),
                'modules' => $modules,
            ];
        }
        return $months;
    }

    /**
     * Method: 8 steps.
     *
     * @return array
     */
    public static function method(): array {
        $method = [];
        for ($i = 1; $i <= 8; $i++) {
            $method[] = ['number' => $i, 'title' => get_string('pub_step' . $i, 'theme_alphatrade'),
                'desc' => get_string('pub_step' . $i . '_desc', 'theme_alphatrade')];
        }
        return $method;
    }

    /**
     * Trainers.
     *
     * @return array
     */
    public static function team(): array {
        $team = [];
        foreach (['nesrine' => 'NM', 'julien' => 'JB'] as $key => $initials) {
            $team[] = [
                'key' => $key,
                'initials' => $initials,
                'name' => get_string('pub_team_' . $key, 'theme_alphatrade'),
                'role' => get_string('pub_team_' . $key . '_role', 'theme_alphatrade'),
                'bio' => get_string('pub_team_' . $key . '_bio', 'theme_alphatrade'),
            ];
        }
        return $team;
    }

    /**
     * Frequently asked questions. Practical questions (format, price, next cohort, certification) only when set.
     *
     * @return array
     */
    public static function faq(): array {
        $facts = self::facts();
        $faq = [];
        foreach (['signals', 'experience', 'duration', 'format', 'price', 'start', 'certificate', 'project', 'admission'] as $key) {
            if (in_array($key, self::FACTS)) {
                if (empty($facts[$key])) {
                    continue;
                }
                $answerkey = $key === 'format' ? 'pub_faq_format_' . self::course_format() . '_answer' : 'pub_faq_' . $key . '_answer';
                $answer = get_string($answerkey, 'theme_alphatrade', $facts[$key]['answer'] ?? null);
            } else {
                $answer = get_string('pub_faq_' . $key . '_answer', 'theme_alphatrade');
            }
            $faq[] = ['question' => get_string('pub_faq_' . $key, 'theme_alphatrade'), 'answer' => $answer];
        }
        return $faq;
    }

    /**
     * Trading level options of the application form.
     *
     * @return array[] [value, label]
     */
    public static function levels(): array {
        return array_map(fn($level) => ['value' => $level, 'label' => get_string('pub_level_' . $level, 'theme_alphatrade')],
            ['beginner', 'intermediate', 'advanced']);
    }

    /**
     * Practical information from the theme settings, keyed by fact; unset values are skipped.
     *
     * @return array[] key => [key, icon, label, value, answer (placeholder of the FAQ answer)]
     */
    public static function facts(): array {
        $facts = [];
        if ($format = self::course_format()) {
            $facts['format'] = ['icon' => 'ph-laptop', 'value' => get_string('pub_format_' . $format, 'theme_alphatrade')];
        }
        if ($start = self::cohort_start()) {
            $date = self::format_date($start);
            $facts['start'] = ['icon' => 'ph-calendar-blank', 'value' => get_string('pub_fact_start_value', 'theme_alphatrade', $date),
                'answer' => $date];
        }
        if ($prices = self::prices()) {
            $labels = array_column($prices, 'label');
            $facts['price'] = ['icon' => 'ph-tag', 'value' => implode(' / ', $labels),
                'answer' => implode(' ' . get_string('pub_or', 'theme_alphatrade') . ' ', $labels)];
        }
        $certificate = trim((string) get_config('theme_alphatrade', 'certificate'));
        if ($certificate !== '') {
            $facts['certificate'] = ['icon' => 'ph-certificate', 'value' => $certificate, 'answer' => $certificate];
        }
        foreach ($facts as $key => $fact) {
            $facts[$key] += ['key' => $key, 'label' => get_string('pub_fact_' . $key, 'theme_alphatrade')];
        }
        return $facts;
    }

    /**
     * Course format setting (online, onsite, blended) or ''.
     *
     * @return string
     */
    public static function course_format(): string {
        $format = (string) get_config('theme_alphatrade', 'courseformat');
        return in_array($format, self::FORMATS) ? $format : '';
    }

    /**
     * Next cohort start (noon UTC) from the YYYY-MM-DD setting, 0 when unset.
     *
     * @return int
     */
    public static function cohort_start(): int {
        $date = trim((string) get_config('theme_alphatrade', 'cohortstart'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 0;
        }
        return (int) strtotime($date . ' 12:00:00 UTC');
    }

    /**
     * Prices from the setting, one "amount CURRENCY" per line (740 EUR).
     *
     * @return array[] [amount (dot decimal), currency (ISO 4217), label]
     */
    public static function prices(): array {
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $prices = [];
        foreach (preg_split('/\R/', (string) get_config('theme_alphatrade', 'prices')) as $line) {
            if (!preg_match('/^\s*(\d+(?:[.,]\d{1,2})?)\s*([A-Za-z]{3})\s*$/', $line, $match)) {
                continue;
            }
            $amount = str_replace(',', '.', $match[1]);
            $currency = strtoupper($match[2]);
            $decimals = strpos($amount, '.') === false ? 0 : 2;
            $prices[] = [
                'amount' => $amount,
                'currency' => $currency,
                'label' => number_format((float) $amount, $decimals, get_string('decsep', 'langconfig'),
                    get_string('thousandssep', 'langconfig')) . "\u{00A0}" . ($symbols[$currency] ?? $currency),
            ];
        }
        return $prices;
    }

    /**
     * Date in the current language ("1er octobre 2026" in French).
     *
     * @param int $time
     * @return string
     */
    protected static function format_date(int $time): string {
        $date = userdate($time, get_string('strftimedate', 'langconfig'), 'UTC');
        if (strpos(current_language(), 'fr') === 0 && gmdate('j', $time) === '1') {
            $date = preg_replace('/^0?1\s/u', "1er\u{00A0}", $date);
        }
        return $date;
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

        // Application: the Alpha Trade form when the app plugin is installed, else a URL or an e-mail.
        $hasform = theme_alphatrade_has_app();
        $applyurl = get_config('theme_alphatrade', 'applyurl');
        $contactemail = get_config('theme_alphatrade', 'contactemail');
        if (!$applyurl && $contactemail) {
            $applyurl = 'mailto:' . $contactemail;
        }

        $data = [
            'pubnav' => $nav,
            'haspubnav' => true,
            'homeurl' => self::url('accueil'),
            'programmeurl' => self::url('programme'),
            'teamurl' => self::url('formateurs'),
            'candidaterurl' => $hasform || !$applyurl ? self::url('candidater') : $applyurl,
            'pipeline' => $pipeline,
            'months' => self::months(),
            'method' => self::method(),
            'team' => self::team(),
            'faq' => self::faq(),
            'levels' => self::levels(),
            'facts' => array_values(self::facts()),
            'hasfacts' => !empty(self::facts()),
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
