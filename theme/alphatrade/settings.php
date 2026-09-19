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
 * Alpha Trade theme settings.
 *
 * @package   theme_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingalphatrade', get_string('configtitle', 'theme_alphatrade'));
    $page = new admin_settingpage('theme_alphatrade_general', get_string('generalsettings', 'theme_alphatrade'));

    $setting = new admin_setting_configcolourpicker('theme_alphatrade/accentcolor',
        get_string('accentcolor', 'theme_alphatrade'), get_string('accentcolor_desc', 'theme_alphatrade'),
        '#C99A3E');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_configcheckbox('theme_alphatrade/publichome',
        get_string('publichome', 'theme_alphatrade'), get_string('publichome_desc', 'theme_alphatrade'), 1);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/applyurl',
        get_string('applyurl', 'theme_alphatrade'), get_string('applyurl_desc', 'theme_alphatrade'), '', PARAM_URL);
    $page->add($setting);

    $formats = [];
    foreach (\theme_alphatrade\output\public_site::FORMATS as $format) {
        $formats[$format] = get_string('pub_format_' . $format, 'theme_alphatrade');
    }
    $setting = new admin_setting_configselect('theme_alphatrade/courseformat',
        get_string('courseformat', 'theme_alphatrade'), get_string('courseformat_desc', 'theme_alphatrade'), 'online',
        ['' => get_string('none')] + $formats);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/cohortstart',
        get_string('cohortstart', 'theme_alphatrade'), get_string('cohortstart_desc', 'theme_alphatrade'), '',
        '/^(\d{4}-\d{2}-\d{2})?$/', 12);
    $page->add($setting);

    $setting = new admin_setting_configtextarea('theme_alphatrade/prices',
        get_string('prices', 'theme_alphatrade'), get_string('prices_desc', 'theme_alphatrade'), '', PARAM_TEXT, 30, 3);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/certificate',
        get_string('certificate', 'theme_alphatrade'), get_string('certificate_desc', 'theme_alphatrade'), '', PARAM_TEXT);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/contactemail',
        get_string('contactemail', 'theme_alphatrade'), get_string('contactemail_desc', 'theme_alphatrade'), '', PARAM_EMAIL);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/contactphone',
        get_string('contactphone', 'theme_alphatrade'), get_string('contactphone_desc', 'theme_alphatrade'), '', PARAM_TEXT);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/country',
        get_string('country', 'theme_alphatrade'), get_string('country_desc', 'theme_alphatrade'), '', PARAM_ALPHA, 4);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/discordurl',
        get_string('discordurl', 'theme_alphatrade'), get_string('discordurl_desc', 'theme_alphatrade'),
        'https://discord.gg/hYUxjtkt7', PARAM_URL);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/instagramurl',
        get_string('instagramurl', 'theme_alphatrade'), get_string('instagramurl_desc', 'theme_alphatrade'), '', PARAM_URL);
    $page->add($setting);

    $setting = new admin_setting_configtext('theme_alphatrade/hellobarurl',
        get_string('hellobarurl', 'theme_alphatrade'), get_string('hellobarurl_desc', 'theme_alphatrade'), '', PARAM_URL);
    $page->add($setting);

    $setting = new admin_setting_configcheckbox('theme_alphatrade/hellobar',
        get_string('hellobar', 'theme_alphatrade'), get_string('hellobar_desc', 'theme_alphatrade'), 1);
    $page->add($setting);

    $setting = new admin_setting_configtextarea('theme_alphatrade/sameas',
        get_string('sameas', 'theme_alphatrade'), get_string('sameas_desc', 'theme_alphatrade'), '', PARAM_RAW);
    $page->add($setting);

    $settings->add($page);
}
