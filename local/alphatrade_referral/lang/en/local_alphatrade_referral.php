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
 * English strings for local_alphatrade_referral.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'Alpha Trade referral';
$string['manage'] = 'Referral administration';
$string['settings'] = 'Settings';
$string['dashboard'] = 'Referral';
$string['dashboard_sub'] = 'Your link, your referrals, your rewards.';
$string['disabled'] = 'The referral programme is currently disabled.';
$string['yourlink'] = 'Your referral link';
$string['yourlink_help'] = 'Share this link. Anyone who creates an account after following it becomes your referral, once and for all.';
$string['copy'] = 'Copy';
$string['copied'] = 'Copied';
$string['kpi_clicks'] = 'Clicks';
$string['kpi_referrals'] = 'Referrals';
$string['kpi_conversions'] = 'Conversions';
$string['kpi_earned'] = 'Earned';
$string['pending_note'] = '{$a->amount} is waiting: a reward is approved {$a->days} days after the conversion.';
$string['myreferrals'] = 'My referrals';
$string['myrewards'] = 'My rewards';
$string['referral'] = 'Referral';
$string['referrer'] = 'Referrer';
$string['status'] = 'Status';
$string['since'] = 'Since';
$string['amount'] = 'Amount';
$string['availableon'] = 'Available on';
$string['noreferrals'] = 'No referral yet. Share your link to get started.';
$string['norewards'] = 'No reward yet.';
$string['discord'] = 'Discord';
$string['discord_help'] = 'Link your Discord account to get your referral notifications there.';
$string['discord_link'] = 'Link my Discord account';
$string['discord_linked'] = 'Linked to {$a}.';
$string['discord_unlink'] = 'Unlink';
$string['discord_notconfigured'] = 'Discord is not configured on this site.';
$string['discord_unlinked'] = 'Discord account unlinked. Your referral history is unchanged.';
$string['discord_failed'] = 'Discord linking failed. Please try again.';
$string['discord_done'] = 'Discord account linked.';
$string['relation_pending'] = 'Pending';
$string['relation_active'] = 'Active';
$string['relation_converted'] = 'Converted';
$string['relation_rewarded'] = 'Rewarded';
$string['relation_cancelled'] = 'Cancelled';
$string['relation_blocked'] = 'Blocked';
$string['reward_pending'] = 'Pending';
$string['reward_approved'] = 'Approved';
$string['reward_paid'] = 'Paid';
$string['reward_cancelled'] = 'Cancelled';
$string['reward_reversed'] = 'Reversed';
$string['task_approve'] = 'Approve due referral rewards';
$string['task_notify'] = 'Send queued Discord referral notifications';
$string['notify_conversion'] = 'One of your referrals just signed up. The reward is being verified.';
$string['notify_reward'] = 'Your referral reward of {$a} has been approved.';
$string['reviewqueue'] = 'Manual review queue';
$string['noreview'] = 'Nothing to review.';
$string['signals'] = 'Signals';
$string['score'] = 'Score';
$string['decision'] = 'Decision';
$string['reason'] = 'Reason';
$string['approve'] = 'Approve';
$string['block'] = 'Block';
$string['reasonrequired'] = 'A reason is required: it is recorded with your name and the date.';
$string['decisionsaved'] = 'Decision recorded.';
$string['lastreferrals'] = 'Latest referrals';
$string['norelations'] = 'No referral recorded yet.';
$string['conversions'] = 'Conversions';
$string['queuestate'] = 'Discord queue: {$a->pending} waiting, {$a->failed} failed';
$string['kpi_codes'] = 'Active codes';
$string['kpi_relations'] = 'Referrals';
$string['kpi_approved'] = 'Approved conversions';
$string['kpi_review'] = 'Under review';
$string['settings_programme'] = 'Programme';
$string['settings_programme_desc'] = 'Moodle is the source of truth: attribution, conversions and rewards all live here.';
$string['settings_fraud'] = 'Anti-fraud';
$string['settings_fraud_desc'] = 'A single signal never decides on its own: it adds to a score. Above the threshold the conversion goes to manual review and the reward is frozen, never cancelled.';
$string['settings_discord'] = 'Discord';
$string['settings_discord_desc'] = 'Discord is an interface, never a source of data. Redirect URI to declare in the Discord application: {$a}';
$string['set_enabled'] = 'Enable the programme';
$string['set_enabled_desc'] = 'Disabled: no click, attribution, conversion or reward is recorded.';
$string['set_cookiedays'] = 'Attribution cookie duration (days)';
$string['set_cookiedays_desc'] = 'How long a click keeps the referrer, before the account is created.';
$string['set_trigger'] = 'A conversion is';
$string['set_trigger_desc'] = 'What turns a referral into a conversion. The architecture recommends a confirmed payment plus the validation delay.';
$string['trigger_payment'] = 'A confirmed payment';
$string['trigger_enrolment'] = 'An enrolment in a course';
$string['trigger_both'] = 'Either of the two';
$string['set_rewardtype'] = 'Reward type';
$string['set_rewardtype_desc'] = 'Fixed amount, or a percentage of the amount paid.';
$string['reward_fixed'] = 'Fixed amount';
$string['reward_percent'] = 'Percentage';
$string['set_rewardamount'] = 'Fixed amount';
$string['set_rewardamount_desc'] = 'Used when the reward type is a fixed amount.';
$string['set_rewardpercent'] = 'Percentage';
$string['set_rewardpercent_desc'] = 'Used when the reward type is a percentage of the amount paid.';
$string['set_currency'] = 'Currency';
$string['set_currency_desc'] = 'ISO code, for example EUR or TND.';
$string['set_validationdays'] = 'Validation delay (days)';
$string['set_validationdays_desc'] = 'Nobody is paid immediately: the delay covers the refund window. The reward stays pending until then.';
$string['set_landing'] = 'Landing page';
$string['set_landing_desc'] = 'Where a referral link sends the visitor, relative to the site root.';
$string['set_shorturl'] = 'Short link /r/CODE';
$string['set_shorturl_desc'] = 'Only once the web server rewrites /r/ to this plugin. Otherwise the long link is used, which always works.';
$string['set_threshold'] = 'Review threshold';
$string['set_threshold_desc'] = 'Fraud score above which a conversion goes to manual review.';
$string['set_ratelimit'] = 'Clicks per minute per IP';
$string['set_ratelimit_desc'] = 'Protects /r/CODE against code guessing. 0 disables the limit.';
$string['set_apisecret'] = 'Webhook secret';
$string['set_apisecret_desc'] = 'Bearer token expected by webhook.php. The REFERRAL_API_SECRET environment variable takes precedence.';
$string['set_discordnotify'] = 'Discord notifications';
$string['set_discordnotify_desc'] = 'Queued and retried by cron: Discord is never blocking.';
$string['set_clientid'] = 'Client ID';
$string['set_clientid_desc'] = 'Discord application. DISCORD_CLIENT_ID takes precedence.';
$string['set_clientsecret'] = 'Client secret';
$string['set_clientsecret_desc'] = 'DISCORD_CLIENT_SECRET takes precedence.';
$string['set_bottoken'] = 'Bot token';
$string['set_bottoken_desc'] = 'Used to send private messages. DISCORD_BOT_TOKEN takes precedence.';
$string['set_guildid'] = 'Server ID';
$string['set_guildid_desc'] = 'Discord server of the community. DISCORD_GUILD_ID takes precedence.';
$string['set_rolesponsor'] = 'Conversions for the Sponsor role';
$string['set_rolesponsor_desc'] = '0 disables this role.';
$string['set_roleambassador'] = 'Conversions for the Ambassador role';
$string['set_roleambassador_desc'] = '0 disables this role.';
$string['set_rolesuper'] = 'Conversions for the Super sponsor role';
$string['set_rolesuper_desc'] = '0 disables this role.';
$string['alphatrade_referral:view'] = 'See every referral';
$string['alphatrade_referral:viewown'] = 'See my own referral page';
$string['alphatrade_referral:manage'] = 'Manage codes and links';
$string['alphatrade_referral:validate'] = 'Decide on the review queue';
$string['alphatrade_referral:configure'] = 'Configure the programme';
$string['privacy:metadata:codes'] = 'The referral code of a user.';
$string['privacy:metadata:codes:code'] = 'The code itself.';
$string['privacy:metadata:codes:status'] = 'Whether the code is active.';
$string['privacy:metadata:codes:timecreated'] = 'When the code was created.';
$string['privacy:metadata:relations'] = 'Who referred whom. The attribution is definitive.';
$string['privacy:metadata:relations:referrerid'] = 'The referrer.';
$string['privacy:metadata:relations:referredid'] = 'The referral.';
$string['privacy:metadata:relations:status'] = 'State of the relationship.';
$string['privacy:metadata:conversions'] = 'Commercial conversions attached to a referral.';
$string['privacy:metadata:conversions:amount'] = 'Amount paid.';
$string['privacy:metadata:conversions:status'] = 'State of the conversion.';
$string['privacy:metadata:rewards'] = 'Rewards owed to a referrer.';
$string['privacy:metadata:rewards:amount'] = 'Reward amount.';
$string['privacy:metadata:rewards:status'] = 'State of the reward.';
$string['privacy:metadata:discord'] = 'The link between a Moodle account and a Discord account.';
$string['privacy:metadata:discord:discorduserid'] = 'The Discord account identifier.';
$string['privacy:metadata:discord:timelinked'] = 'When the accounts were linked.';
$string['privacy:metadata:discordapi'] = 'Private messages sent to the member through the Discord API.';
$string['privacy:metadata:discordapi:discorduserid'] = 'The recipient account.';
$string['privacy:metadata:discordapi:content'] = 'A neutral notification: status and amount, nothing else.';
$string['notlinked'] = 'This Discord account is not linked to an Alpha Trade account.';
