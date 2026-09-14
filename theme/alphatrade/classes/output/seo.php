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
 * Search and answer engines for the public site: meta tags, link previews (Open Graph), schema.org JSON-LD,
 * robots.txt, sitemap.xml and llms.txt. Everything is built from the public site copy (language files) and
 * the theme settings, so it follows the wwwroot and the texts edited in Moodle.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class seo {

    /** @var string Square share image (link previews: WhatsApp shows it next to the text). */
    const SHARE_IMAGE = 'share-logo.jpg';

    /** @var int Share image side, in pixels. */
    const SHARE_SIZE = 600;

    /** @var string[] Crawlers explicitly welcome: search engines and AI assistants. */
    const BOTS = ['*', 'Googlebot', 'Bingbot', 'Applebot', 'DuckDuckBot', 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User',
        'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended',
        'Applebot-Extended', 'Meta-ExternalAgent', 'MistralAI-User', 'CCBot'];

    /** @var string[] Private Moodle areas (login required), kept out of crawlers. */
    const PRIVATE_PATHS = ['/admin/', '/auth/', '/badges/', '/blocks/', '/blog/', '/calendar/', '/cohort/', '/comment/',
        '/competency/', '/contentbank/', '/course/', '/enrol/', '/files/', '/grade/', '/group/', '/h5p/', '/local/',
        '/login/', '/message/', '/mod/', '/my/', '/notes/', '/payment/', '/pluginfile.php', '/question/', '/rating/',
        '/report/', '/repository/', '/search/', '/tag/', '/user/', '/webservice/'];

    /** @var public_site */
    protected $site;

    /**
     * Constructor.
     *
     * @param public_site $site
     */
    public function __construct(public_site $site) {
        $this->site = $site;
    }

    /**
     * Strip Moodle's own description (site summary) and "moodle, ..." keywords from standard_head_html():
     * the public site prints its own tags per view.
     *
     * @param string $html
     * @return string
     */
    public static function clean_head(string $html): string {
        return preg_replace('~<meta name="(description|keywords)" content="[^"]*" />\s*~', '', $html);
    }

    /**
     * Template data for theme_alphatrade/public_head.
     *
     * @return array
     */
    public function head(): array {
        global $CFG;

        $view = $this->site->view();
        $sitename = self::str('seo_sitename');
        $title = self::str('seo_title_' . $view);
        if ($view !== 'accueil') {
            $title .= ' | ' . self::str('pluginname');
        }
        $image = self::share_image();

        return [
            'title' => $title,
            'description' => self::str('seo_desc_' . $view),
            'canonical' => public_site::url($view),
            'sitename' => $sitename,
            'locale' => self::locale(),
            'image' => $image,
            'imagesize' => self::SHARE_SIZE,
            'imagealt' => self::str('seo_share_alt'),
            'indexable' => ($CFG->allowindexing ?? 0) != 2,
            'llms' => (new moodle_url('/llms.txt'))->out(false),
            'jsonld' => json_encode($this->graph($title),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP),
        ];
    }

    /**
     * schema.org graph of the current view.
     *
     * @param string $title Page title.
     * @return array
     */
    protected function graph(string $title): array {
        $view = $this->site->view();
        $home = public_site::url('accueil');
        $canonical = public_site::url($view);
        $orgid = $home . '#organization';
        $language = current_language();

        $organization = [
            '@type' => 'EducationalOrganization',
            '@id' => $orgid,
            'name' => self::str('seo_sitename'),
            'alternateName' => ['Alpha Trade', 'AlphaTrade'],
            'url' => $home,
            'logo' => ['@type' => 'ImageObject', 'url' => self::share_image(), 'width' => self::SHARE_SIZE,
                'height' => self::SHARE_SIZE],
            'image' => self::share_image(),
            'description' => self::str('seo_org_desc'),
            'slogan' => self::str('pub_tagline'),
            'knowsAbout' => self::topics(),
        ];
        $organization += self::contact();

        $graph = [
            $organization,
            [
                '@type' => 'WebSite',
                '@id' => $home . '#website',
                'url' => $home,
                'name' => self::str('seo_sitename'),
                'inLanguage' => $language,
                'publisher' => ['@id' => $orgid],
            ],
        ];

        $page = [
            '@type' => $view === 'faq' ? 'FAQPage' : 'WebPage',
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => self::str('seo_desc_' . $view),
            'inLanguage' => $language,
            'isPartOf' => ['@id' => $home . '#website'],
            'about' => ['@id' => $orgid],
            'primaryImageOfPage' => self::share_image(),
            'dateModified' => date('c', self::modified()),
        ];

        if ($view !== 'accueil') {
            $label = $view === 'candidater' ? self::str('pub_apply') : self::str('pub_nav_' . $view);
            $page['breadcrumb'] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => self::str('pub_nav_accueil'), 'item' => $home],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $label, 'item' => $canonical],
                ],
            ];
        }

        if ($view === 'faq') {
            $page['mainEntity'] = array_map(fn($item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ], public_site::faq());
        }
        $graph[] = $page;

        if (in_array($view, ['accueil', 'programme', 'formateurs'])) {
            foreach (public_site::team() as $person) {
                $graph[] = [
                    '@type' => 'Person',
                    '@id' => self::person_id($person['key']),
                    'name' => $person['name'],
                    'description' => $person['bio'],
                    'knowsAbout' => array_map('trim', explode('·', $person['role'])),
                    'worksFor' => ['@id' => $orgid],
                ];
            }
        }

        if (in_array($view, ['accueil', 'programme'])) {
            $modules = [];
            $sections = [];
            foreach (public_site::months() as $month) {
                $sections[] = [
                    '@type' => 'Syllabus',
                    'name' => self::str('pub_month', $month['number']) . ' - ' . $month['label'],
                    'description' => $month['summary'],
                ];
                foreach ($month['modules'] as $module) {
                    $modules[] = $module['title'];
                }
            }
            $course = [
                '@type' => 'Course',
                '@id' => public_site::url('programme') . '#course',
                'name' => self::str('seo_course_name'),
                'description' => self::str('seo_desc_programme'),
                'url' => public_site::url('programme'),
                'inLanguage' => $language,
                'provider' => ['@id' => $orgid],
                'instructor' => array_map(fn($person) => ['@id' => self::person_id($person['key'])], public_site::team()),
                'timeRequired' => 'P3M',
                'teaches' => $modules,
                'syllabusSections' => $sections,
                'image' => self::share_image(),
            ];
            $facts = public_site::facts();
            if (!empty($facts['certificate'])) {
                $course['educationalCredentialAwarded'] = $facts['certificate']['value'];
            }
            $instance = array_filter([
                'courseMode' => public_site::course_format(),
                'startDate' => public_site::cohort_start() ? gmdate('Y-m-d', public_site::cohort_start()) : '',
            ]);
            if ($instance) {
                $course['hasCourseInstance'] = ['@type' => 'CourseInstance', 'inLanguage' => $language] + $instance;
            }
            foreach (public_site::prices() as $price) {
                $course['offers'][] = [
                    '@type' => 'Offer',
                    'category' => 'Paid',
                    'price' => $price['amount'],
                    'priceCurrency' => $price['currency'],
                    'url' => public_site::url('candidater'),
                ];
            }
            $graph[] = $course;
        }

        if ($view === 'methode') {
            $graph[] = [
                '@type' => 'HowTo',
                '@id' => $canonical . '#method',
                'name' => self::str('pub_method_h1'),
                'description' => self::str('pub_method_lead'),
                'inLanguage' => $language,
                'step' => array_map(fn($step) => [
                    '@type' => 'HowToStep',
                    'position' => $step['number'],
                    'name' => $step['title'],
                    'text' => $step['desc'],
                ], public_site::method()),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * robots.txt: public site and theme assets open to every crawler (AI assistants included), Moodle private areas closed.
     *
     * @return string
     */
    public static function robots(): string {
        $lines = ['# ' . self::str('seo_sitename')];
        foreach (self::BOTS as $bot) {
            $lines[] = 'User-agent: ' . $bot;
        }
        $lines[] = 'Allow: /';
        foreach (self::PRIVATE_PATHS as $path) {
            $lines[] = 'Disallow: ' . $path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . (new moodle_url('/sitemap.xml'))->out(false);
        return implode("\n", $lines) . "\n";
    }

    /**
     * sitemap.xml: the public views and the programme brochure.
     *
     * @return string
     */
    public static function sitemap(): string {
        $lastmod = date('Y-m-d', self::modified());
        $urls = [];
        foreach (self::views() as $view) {
            $urls[] = public_site::url($view);
        }
        if (get_config('theme_alphatrade', 'publichome')) {
            $urls[] = self::programme_pdf();
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1) . '</loc><lastmod>' . $lastmod . '</lastmod></url>' . "\n";
        }
        return $xml . '</urlset>' . "\n";
    }

    /**
     * llms.txt (https://llmstxt.org): what Alpha Trade is and where to read it, for AI assistants.
     * The full variant carries the whole public copy (programme, method, trainers, FAQ) in plain Markdown.
     *
     * @param bool $full
     * @return string
     */
    public static function llms(bool $full = false): string {
        global $SITE;

        $md = ['# ' . self::str('seo_sitename'), '', '> ' . self::str('seo_org_desc'), ''];
        $md[] = self::str('pub_tagline');
        $md[] = '';

        $md[] = '## ' . self::str('seo_practical');
        $md[] = '';
        $md[] = '- ' . self::str('pub_nav_programme') . ' : ' . self::str('pub_programme_h1');
        foreach (public_site::facts() as $fact) {
            $md[] = '- ' . $fact['label'] . ' : ' . $fact['value'];
        }
        $md[] = '';

        $contact = self::contact();
        if ($contact) {
            $md[] = '## ' . self::str('seo_contact');
            $md[] = '';
            $md[] = '- ' . self::str('seo_website') . ' : ' . public_site::url('accueil');
            if (!empty($contact['email'])) {
                $md[] = '- ' . self::str('email', null, 'moodle') . ' : ' . $contact['email'];
            }
            if (!empty($contact['telephone'])) {
                $md[] = '- ' . self::str('pub_apply_phone') . ' : ' . $contact['telephone'];
            }
            if (!empty($contact['sameAs'])) {
                foreach ($contact['sameAs'] as $url) {
                    $md[] = '- ' . $url;
                }
            }
            $md[] = '';
        }

        if (!$full) {
            $md[] = '## ' . self::str('seo_pages');
            $md[] = '';
            foreach (self::views() as $view) {
                $md[] = '- [' . self::str('seo_title_' . $view) . '](' . public_site::url($view) . '): ' . self::str('seo_desc_' . $view);
            }
            $md[] = '';
            if (get_config('theme_alphatrade', 'publichome')) {
                $md[] = '## ' . self::str('seo_documents');
                $md[] = '';
                $md[] = '- [' . self::str('pub_download_programme') . ' (PDF)](' . self::programme_pdf() . ')';
                $md[] = '';
            }
            $md[] = '## Optional';
            $md[] = '';
            $md[] = '- [' . self::str('seo_llms_full') . '](' . (new moodle_url('/llms-full.txt'))->out(false) . ')';
            return self::plain(implode("\n", $md));
        }

        $summary = trim(html_to_text(format_text($SITE->summary, FORMAT_HTML), 0, false));
        if ($summary !== '') {
            $md[] = '## ' . self::str('seo_about');
            $md[] = '';
            $md[] = $summary;
            $md[] = '';
        }

        $md[] = '## ' . self::str('pub_programme_title') . ' (' . public_site::url('programme') . ')';
        $md[] = '';
        $md[] = self::str('pub_hero_lead');
        $md[] = '';
        foreach (public_site::months() as $month) {
            $md[] = '### ' . self::str('pub_month', $month['number']) . ' - ' . $month['label'];
            $md[] = '';
            $md[] = $month['summary'];
            $md[] = '';
            foreach ($month['modules'] as $module) {
                $md[] = '- ' . $module['number'] . '. ' . $module['title'] . ' : ' . $module['desc'];
            }
            $md[] = '';
        }

        $md[] = '## ' . self::str('pub_method_h1') . ' (' . public_site::url('methode') . ')';
        $md[] = '';
        $md[] = self::str('pub_method_lead');
        $md[] = '';
        foreach (public_site::method() as $step) {
            $md[] = $step['number'] . '. ' . $step['title'] . ' : ' . $step['desc'];
        }
        $md[] = '';
        $md[] = self::str('pub_method_warning');
        $md[] = '';

        $md[] = '## ' . self::str('pub_team_h1') . ' (' . public_site::url('formateurs') . ')';
        $md[] = '';
        $md[] = self::str('pub_team_lead');
        $md[] = '';
        foreach (public_site::team() as $person) {
            $md[] = '### ' . $person['name'] . ' - ' . $person['role'];
            $md[] = '';
            $md[] = $person['bio'];
            $md[] = '';
        }

        $md[] = '## ' . self::str('pub_faq_h1') . ' (' . public_site::url('faq') . ')';
        $md[] = '';
        foreach (public_site::faq() as $item) {
            $md[] = '### ' . $item['question'];
            $md[] = '';
            $md[] = $item['answer'];
            $md[] = '';
        }

        $md[] = '## ' . self::str('pub_apply_h1') . ' (' . public_site::url('candidater') . ')';
        $md[] = '';
        $md[] = self::str('pub_faq_admission_answer');
        return self::plain(implode("\n", $md));
    }

    /**
     * Absolute URL of the share image.
     *
     * @return string
     */
    public static function share_image(): string {
        return (new moodle_url('/theme/alphatrade/files/' . self::SHARE_IMAGE))->out(false);
    }

    /**
     * Public views listed in the sitemap and llms.txt (only the home when the public site is off).
     *
     * @return string[]
     */
    protected static function views(): array {
        return get_config('theme_alphatrade', 'publichome') ? public_site::VIEWS : ['accueil'];
    }

    /**
     * Organisation contact details from the theme settings (schema.org property names).
     *
     * @return array
     */
    protected static function contact(): array {
        $config = get_config('theme_alphatrade');
        $contact = [];
        if (!empty($config->contactemail)) {
            $contact['email'] = $config->contactemail;
        }
        if (!empty($config->contactphone)) {
            $contact['telephone'] = $config->contactphone;
        }
        if (!empty($config->country)) {
            $contact['address'] = ['@type' => 'PostalAddress', 'addressCountry' => strtoupper($config->country)];
        }
        $sameas = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($config->sameas ?? ''))),
            fn($url) => clean_param($url, PARAM_URL) !== '' && preg_match('~^https?://~', $url)));
        if ($sameas) {
            $contact['sameAs'] = $sameas;
        }
        return $contact;
    }

    /**
     * Topics taught (programme months and module titles).
     *
     * @return string[]
     */
    protected static function topics(): array {
        $topics = [];
        foreach (public_site::months() as $month) {
            foreach ($month['modules'] as $module) {
                if ($module['number'] !== '12') {
                    $topics[] = $module['title'];
                }
            }
        }
        return $topics;
    }

    /**
     * Last change of the public copy (language pack of the theme, then Moodle's language customisations).
     *
     * @return int
     */
    protected static function modified(): int {
        global $CFG;
        $time = (int) @filemtime(__DIR__ . '/../../lang/en/theme_alphatrade.php');
        $custom = $CFG->langlocalroot . '/' . current_language() . '_local/theme_alphatrade.php';
        if (is_readable($custom)) {
            $time = max($time, (int) filemtime($custom));
        }
        return $time ?: time();
    }

    /**
     * Open Graph locale (fr_FR) from the language pack.
     *
     * @return string
     */
    protected static function locale(): string {
        $locale = explode('.', get_string('locale', 'langconfig'))[0];
        return preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale) ? $locale : 'fr_FR';
    }

    /**
     * Stable identifier of a trainer.
     *
     * @param string $key
     * @return string
     */
    protected static function person_id(string $key): string {
        return public_site::url('formateurs') . '#' . $key;
    }

    /**
     * Programme brochure URL.
     *
     * @return string
     */
    protected static function programme_pdf(): string {
        return (new moodle_url('/theme/alphatrade/files/' . public_site::PROGRAMME_PDF))->out(false);
    }

    /**
     * A theme string.
     *
     * @param string $key
     * @param mixed $a
     * @param string $component
     * @return string
     */
    protected static function str(string $key, $a = null, string $component = 'theme_alphatrade'): string {
        return get_string($key, $component, $a);
    }

    /**
     * Plain text output: no em dash (house style), trailing newline.
     *
     * @param string $text
     * @return string
     */
    protected static function plain(string $text): string {
        return str_replace(["\u{2014}", "\r"], ['-', ''], rtrim($text)) . "\n";
    }
}
