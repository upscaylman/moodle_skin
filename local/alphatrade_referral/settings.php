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
 * Réglages du parrainage (§ 11). Les secrets Discord et le secret du webhook acceptent une
 * variable d'environnement, qui prime toujours sur la valeur enregistrée ici.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $category = new admin_category('local_alphatrade_referral_cat',
        get_string('pluginname', 'local_alphatrade_referral'));
    $ADMIN->add('localplugins', $category);

    // Écran d'administration : file de revue et vue d'ensemble.
    $ADMIN->add('local_alphatrade_referral_cat', new admin_externalpage(
        'local_alphatrade_referral_manage',
        get_string('manage', 'local_alphatrade_referral'),
        new moodle_url('/local/alphatrade_referral/manage.php'),
        'local/alphatrade_referral:view'));

    $settings = new admin_settingpage('local_alphatrade_referral_settings',
        get_string('settings', 'local_alphatrade_referral'), 'local/alphatrade_referral:configure');
    $ADMIN->add('local_alphatrade_referral_cat', $settings);

    // ---------------------------------------------------------------- Programme.
    $settings->add(new admin_setting_heading('local_alphatrade_referral/programme',
        get_string('settings_programme', 'local_alphatrade_referral'),
        get_string('settings_programme_desc', 'local_alphatrade_referral')));

    $settings->add(new admin_setting_configcheckbox('local_alphatrade_referral/enabled',
        get_string('set_enabled', 'local_alphatrade_referral'),
        get_string('set_enabled_desc', 'local_alphatrade_referral'), 1));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/cookiedays',
        get_string('set_cookiedays', 'local_alphatrade_referral'),
        get_string('set_cookiedays_desc', 'local_alphatrade_referral'), 30, PARAM_INT));

    $settings->add(new admin_setting_configselect('local_alphatrade_referral/conversiontrigger',
        get_string('set_trigger', 'local_alphatrade_referral'),
        get_string('set_trigger_desc', 'local_alphatrade_referral'), 'payment', [
            'payment' => get_string('trigger_payment', 'local_alphatrade_referral'),
            'enrolment' => get_string('trigger_enrolment', 'local_alphatrade_referral'),
            'both' => get_string('trigger_both', 'local_alphatrade_referral'),
        ]));

    $settings->add(new admin_setting_configselect('local_alphatrade_referral/rewardtype',
        get_string('set_rewardtype', 'local_alphatrade_referral'),
        get_string('set_rewardtype_desc', 'local_alphatrade_referral'), 'fixed', [
            'fixed' => get_string('reward_fixed', 'local_alphatrade_referral'),
            'percent' => get_string('reward_percent', 'local_alphatrade_referral'),
        ]));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/rewardamount',
        get_string('set_rewardamount', 'local_alphatrade_referral'),
        get_string('set_rewardamount_desc', 'local_alphatrade_referral'), 10, PARAM_FLOAT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/rewardpercent',
        get_string('set_rewardpercent', 'local_alphatrade_referral'),
        get_string('set_rewardpercent_desc', 'local_alphatrade_referral'), 10, PARAM_FLOAT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/currency',
        get_string('set_currency', 'local_alphatrade_referral'),
        get_string('set_currency_desc', 'local_alphatrade_referral'), 'EUR', PARAM_ALPHA, 5));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/validationdays',
        get_string('set_validationdays', 'local_alphatrade_referral'),
        get_string('set_validationdays_desc', 'local_alphatrade_referral'), 14, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/landingpath',
        get_string('set_landing', 'local_alphatrade_referral'),
        get_string('set_landing_desc', 'local_alphatrade_referral'), '/', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('local_alphatrade_referral/shorturl',
        get_string('set_shorturl', 'local_alphatrade_referral'),
        get_string('set_shorturl_desc', 'local_alphatrade_referral'), 0));

    // ---------------------------------------------------------------- Anti-fraude.
    $settings->add(new admin_setting_heading('local_alphatrade_referral/fraud',
        get_string('settings_fraud', 'local_alphatrade_referral'),
        get_string('settings_fraud_desc', 'local_alphatrade_referral')));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/fraudthreshold',
        get_string('set_threshold', 'local_alphatrade_referral'),
        get_string('set_threshold_desc', 'local_alphatrade_referral'), 60, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/ratelimit',
        get_string('set_ratelimit', 'local_alphatrade_referral'),
        get_string('set_ratelimit_desc', 'local_alphatrade_referral'), 30, PARAM_INT));

    $settings->add(new admin_setting_configpasswordunmask('local_alphatrade_referral/apisecret',
        get_string('set_apisecret', 'local_alphatrade_referral'),
        get_string('set_apisecret_desc', 'local_alphatrade_referral'), ''));

    // ---------------------------------------------------------------- Discord.
    $settings->add(new admin_setting_heading('local_alphatrade_referral/discord',
        get_string('settings_discord', 'local_alphatrade_referral'),
        get_string('settings_discord_desc', 'local_alphatrade_referral',
            (new moodle_url('/local/alphatrade_referral/discord.php', ['action' => 'callback']))->out(false))));

    $settings->add(new admin_setting_configcheckbox('local_alphatrade_referral/discordnotify',
        get_string('set_discordnotify', 'local_alphatrade_referral'),
        get_string('set_discordnotify_desc', 'local_alphatrade_referral'), 1));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/discordclientid',
        get_string('set_clientid', 'local_alphatrade_referral'),
        get_string('set_clientid_desc', 'local_alphatrade_referral'), '', PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configpasswordunmask('local_alphatrade_referral/discordclientsecret',
        get_string('set_clientsecret', 'local_alphatrade_referral'),
        get_string('set_clientsecret_desc', 'local_alphatrade_referral'), ''));

    $settings->add(new admin_setting_configpasswordunmask('local_alphatrade_referral/discordbottoken',
        get_string('set_bottoken', 'local_alphatrade_referral'),
        get_string('set_bottoken_desc', 'local_alphatrade_referral'), ''));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/discordguildid',
        get_string('set_guildid', 'local_alphatrade_referral'),
        get_string('set_guildid_desc', 'local_alphatrade_referral'), '', PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/rolesponsor',
        get_string('set_rolesponsor', 'local_alphatrade_referral'),
        get_string('set_rolesponsor_desc', 'local_alphatrade_referral'), 1, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/roleambassador',
        get_string('set_roleambassador', 'local_alphatrade_referral'),
        get_string('set_roleambassador_desc', 'local_alphatrade_referral'), 5, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_alphatrade_referral/rolesuper',
        get_string('set_rolesuper', 'local_alphatrade_referral'),
        get_string('set_rolesuper_desc', 'local_alphatrade_referral'), 20, PARAM_INT));
}
