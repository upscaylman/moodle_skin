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
 * Alpha Trade app settings: maps the Alpha Trade spaces onto Moodle courses and activities.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_alphatrade', get_string('pluginname', 'local_alphatrade'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        global $DB;

        $courses = [0 => get_string('none')];
        foreach ($DB->get_records_select_menu('course', 'id <> ?', [SITEID], 'fullname ASC', 'id, fullname') as $id => $name) {
            $courses[$id] = format_string($name);
        }

        $forums = [0 => get_string('none')];
        $sql = "SELECT cm.id, f.name, c.shortname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'forum'
                  JOIN {forum} f ON f.id = cm.instance
                  JOIN {course} c ON c.id = cm.course
              ORDER BY c.shortname, f.name";
        foreach ($DB->get_records_sql($sql) as $forum) {
            $forums[$forum->id] = format_string($forum->shortname) . ' - ' . format_string($forum->name);
        }

        // Programme.
        $settings->add(new admin_setting_heading('local_alphatrade/programmeheading',
            get_string('settings_programme', 'local_alphatrade'), get_string('settings_programme_desc', 'local_alphatrade')));
        $settings->add(new admin_setting_configselect('local_alphatrade/programmecourse',
            get_string('programmecourse', 'local_alphatrade'), get_string('programmecourse_desc', 'local_alphatrade'), 0, $courses));
        $settings->add(new admin_setting_configtext('local_alphatrade/programmeweeks',
            get_string('programmeweeks', 'local_alphatrade'), '', 12, PARAM_INT, 4));
        $settings->add(new admin_setting_configtext('local_alphatrade/monthsplit',
            get_string('monthsplit', 'local_alphatrade'), get_string('monthsplit_desc', 'local_alphatrade'), '3,4,5', PARAM_SEQUENCE));
        $settings->add(new admin_setting_configtext('local_alphatrade/projectsection',
            get_string('projectsection', 'local_alphatrade'), get_string('projectsection_desc', 'local_alphatrade'), 0, PARAM_INT, 4));
        $settings->add(new admin_setting_configtext('local_alphatrade/certificatecm',
            get_string('certificatecm', 'local_alphatrade'), get_string('certificatecm_desc', 'local_alphatrade'), 0, PARAM_INT, 8));
        $settings->add(new admin_setting_configtext('local_alphatrade/minbacktesttrades',
            get_string('minbacktesttrades', 'local_alphatrade'), '', 100, PARAM_INT, 6));

        // Practice and resources.
        $settings->add(new admin_setting_heading('local_alphatrade/spacesheading',
            get_string('settings_spaces', 'local_alphatrade'), ''));
        $settings->add(new admin_setting_configselect('local_alphatrade/practicecourse',
            get_string('practicecourse', 'local_alphatrade'), get_string('practicecourse_desc', 'local_alphatrade'), 0, $courses));
        $settings->add(new admin_setting_configtext('local_alphatrade/casessection',
            get_string('casessection', 'local_alphatrade'), '', 1, PARAM_INT, 4));
        $settings->add(new admin_setting_configtext('local_alphatrade/challengessection',
            get_string('challengessection', 'local_alphatrade'), '', 2, PARAM_INT, 4));
        $settings->add(new admin_setting_configselect('local_alphatrade/resourcescourse',
            get_string('resourcescourse', 'local_alphatrade'), get_string('resourcescourse_desc', 'local_alphatrade'), 0, $courses));
        $settings->add(new admin_setting_configselect('local_alphatrade/qaforum',
            get_string('qaforum', 'local_alphatrade'), '', 0, $forums));
        $settings->add(new admin_setting_configselect('local_alphatrade/analysisforum',
            get_string('analysisforum', 'local_alphatrade'), '', 0, $forums));

        // Behaviour.
        $settings->add(new admin_setting_heading('local_alphatrade/behaviourheading',
            get_string('settings_behaviour', 'local_alphatrade'), ''));
        $settings->add(new admin_setting_configcheckbox('local_alphatrade/redirectdashboard',
            get_string('redirectdashboard', 'local_alphatrade'), get_string('redirectdashboard_desc', 'local_alphatrade'), 1));
        $settings->add(new admin_setting_configcheckbox('local_alphatrade/redirectcourse',
            get_string('redirectcourse', 'local_alphatrade'), get_string('redirectcourse_desc', 'local_alphatrade'), 1));
        $settings->add(new admin_setting_configtext('local_alphatrade/inactivedays',
            get_string('inactivedays', 'local_alphatrade'), get_string('inactivedays_desc', 'local_alphatrade'), 7, PARAM_INT, 4));
        $settings->add(new admin_setting_configcheckbox('local_alphatrade/redirectmessages',
            get_string('redirectmessages', 'local_alphatrade'), get_string('redirectmessages_desc', 'local_alphatrade'), 1));

        // Market data proxy (API key stays on the server).
        $settings->add(new admin_setting_heading('local_alphatrade/marketheading',
            get_string('settings_marketdata', 'local_alphatrade'), get_string('settings_marketdata_desc', 'local_alphatrade')));
        $settings->add(new admin_setting_configtextarea('local_alphatrade/markets',
            get_string('markets', 'local_alphatrade'), get_string('markets_desc', 'local_alphatrade'),
            \local_alphatrade\local\markets::DEFAULT, PARAM_TEXT, 60, 6));
        $settings->add(new admin_setting_configpasswordunmask('local_alphatrade/lse_apikey',
            get_string('lse_apikey', 'local_alphatrade'), get_string('lse_apikey_desc', 'local_alphatrade'), ''));
        $settings->add(new admin_setting_configtext('local_alphatrade/lse_baseurl',
            get_string('lse_baseurl', 'local_alphatrade'), '', \local_alphatrade\local\marketdata::DEFAULT_BASEURL, PARAM_URL, 50));
        $settings->add(new admin_setting_configtext('local_alphatrade/lse_cachettl',
            get_string('lse_cachettl', 'local_alphatrade'), '', 3600, PARAM_INT, 6));
        $settings->add(new admin_setting_configtext('local_alphatrade/lse_ratelimit',
            get_string('lse_ratelimit', 'local_alphatrade'), get_string('lse_ratelimit_desc', 'local_alphatrade'), 120, PARAM_INT, 6));
    }
}
