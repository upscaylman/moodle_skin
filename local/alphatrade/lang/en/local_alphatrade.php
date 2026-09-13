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
 * English strings for the Alpha Trade app.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Alpha Trade';

// Capabilities, messages, privacy.
$string['local/alphatrade:managecharts'] = 'Create and edit chart analysis exercises';
$string['local/alphatrade:reviewanalysis'] = 'Correct chart analyses';
$string['local/alphatrade:viewstudentdata'] = 'View students\' backtests, journals and analyses';
$string['local/alphatrade:viewteacher'] = 'Access the trainer space';
$string['messageprovider:analysisreviewed'] = 'Chart analysis corrected';
$string['notification_reviewed_body'] = 'Your analysis "{$a->chart}" has been corrected: {$a->score} / 100. See the correction: {$a->url}';
$string['notification_reviewed_subject'] = 'Correction available: {$a}';
$string['privacy:metadata:asset'] = 'Traded asset';
$string['privacy:metadata:assetclass'] = 'Asset class';
$string['privacy:metadata:bias'] = 'Bias chosen in the analysis';
$string['privacy:metadata:chartid'] = 'Related analysis exercise';
$string['privacy:metadata:core_files'] = 'Screenshots attached to journal trades';
$string['privacy:metadata:core_message'] = 'Notifications sent when an analysis is corrected';
$string['privacy:metadata:description'] = 'Strategy rules';
$string['privacy:metadata:direction'] = 'Trade direction (buy or sell)';
$string['privacy:metadata:emotion'] = 'Declared emotional state';
$string['privacy:metadata:entry'] = 'Entry price';
$string['privacy:metadata:feedback'] = 'Trainer feedback';
$string['privacy:metadata:followedplan'] = 'Whether the trading plan was followed';
$string['privacy:metadata:liquidity'] = 'Liquidity analysis';
$string['privacy:metadata:local_alphatrade_analysis'] = 'Chart analyses submitted by the student';
$string['privacy:metadata:local_alphatrade_bttrade'] = 'Historical trades entered in the Backtesting Lab';
$string['privacy:metadata:local_alphatrade_journal'] = 'Trading journal entries';
$string['privacy:metadata:local_alphatrade_strategy'] = 'Backtesting Lab strategies';
$string['privacy:metadata:market'] = 'Market (symbol)';
$string['privacy:metadata:marketcontext'] = 'Market context before the trade';
$string['privacy:metadata:name'] = 'Strategy name';
$string['privacy:metadata:notes'] = 'Trade notes';
$string['privacy:metadata:period'] = 'Tested period';
$string['privacy:metadata:reason'] = 'Reason for entering';
$string['privacy:metadata:resultr'] = 'Result in R';
$string['privacy:metadata:review'] = 'Review after the trade';
$string['privacy:metadata:riskpct'] = 'Risk as a percentage of capital';
$string['privacy:metadata:scenario'] = 'Proposed scenario';
$string['privacy:metadata:score'] = 'Score out of 100';
$string['privacy:metadata:setup'] = 'Setup used';
$string['privacy:metadata:status'] = 'Correction status';
$string['privacy:metadata:stoploss'] = 'Stop loss';
$string['privacy:metadata:strategyid'] = 'Strategy of the trade';
$string['privacy:metadata:structure'] = 'Structure analysis';
$string['privacy:metadata:takeprofit'] = 'Take profit';
$string['privacy:metadata:timecreated'] = 'Creation date';
$string['privacy:metadata:timeframe'] = 'Timeframe';
$string['privacy:metadata:timemodified'] = 'Modification date';
$string['privacy:metadata:timereviewed'] = 'Correction date';
$string['privacy:metadata:tradedate'] = 'Trade date';
$string['privacy:metadata:userid'] = 'Student who owns the data';

// Settings.
$string['analysisforum'] = '"Market analyses" forum';
$string['casessection'] = 'Case studies section (number)';
$string['certificatecm'] = 'Certificate activity (cmid)';
$string['certificatecm_desc'] = 'Id of the activity that issues the certificate (for example mod_customcert). 0: none.';
$string['challengessection'] = 'Challenges section (number)';
$string['inactivedays'] = 'Inactivity alert (days)';
$string['inactivedays_desc'] = 'Beyond this, the student is shown as blocked in the trainer space.';
$string['minbacktesttrades'] = 'Minimum backtested trades (certification)';
$string['monthsplit'] = 'Modules per month';
$string['monthsplit_desc'] = 'Number of modules in each month, comma separated. Example: 3,4,5.';
$string['practicecourse'] = '"Practice" course';
$string['practicecourse_desc'] = 'Course holding case studies and challenges (one section each).';
$string['programmecourse'] = 'Programme course';
$string['programmecourse_desc'] = 'The 3-month programme: each section is a module, each activity a lesson, quizzes are assessments. Enable completion tracking.';
$string['programmeweeks'] = 'Programme length (weeks)';
$string['projectsection'] = 'Final project section (number)';
$string['projectsection_desc'] = 'Section holding the Alpha Trading System steps. 0: last module.';
$string['qaforum'] = '"Questions and answers" forum';
$string['redirectcourse'] = 'Redirect the programme course';
$string['redirectcourse_desc'] = 'Students opening the course page land on "My programme" (trainers keep the Moodle page).';
$string['redirectdashboard'] = 'Redirect the Moodle dashboard';
$string['redirectdashboard_desc'] = '/my/ opens the Alpha Trade dashboard (except for administrators).';
$string['resourcescourse'] = '"Resources" course';
$string['resourcescourse_desc'] = 'Course whose activities make up the library (PDF, videos, checklists, templates).';
$string['settings_behaviour'] = 'Behaviour';
$string['settings_programme'] = 'Programme';
$string['settings_programme_desc'] = 'Moodle stays the invisible engine: these settings map the Alpha Trade spaces onto Moodle courses and activities.';
$string['settings_spaces'] = 'Practice, resources and community';

// General.
$string['actions'] = 'Actions';
$string['add'] = 'Add';
$string['all'] = 'All';
$string['backtoparcours'] = 'Back to the programme';
$string['configureprogramme'] = 'Configure the programme';
$string['continue'] = 'Continue';
$string['date'] = 'Date';
$string['deleted'] = 'Item deleted.';
$string['download'] = 'Download';
$string['feedback'] = 'Feedback';
$string['filter'] = 'Filter';
$string['filters'] = 'Filters';
$string['hidden'] = 'hidden';
$string['invalidnumber'] = 'Invalid number.';
$string['new'] = 'New';
$string['noprogramme'] = 'The programme is not configured yet.';
$string['open'] = 'Open';
$string['progress'] = 'Progress';
$string['resetfilters'] = 'Reset';
$string['result'] = 'Result';
$string['results'] = 'Results';
$string['settings'] = 'Settings';
$string['start'] = 'Start';
$string['state'] = 'State';
$string['status'] = 'Status';
$string['status_current'] = 'In progress';
$string['status_done'] = 'Completed';
$string['status_locked'] = 'Locked';
$string['status_todo'] = 'To do';
$string['use'] = 'Use';

// Dashboard.
$string['backtestedtrades'] = 'Backtested trades';
$string['backtestedtrades_sub'] = 'in the Backtesting Lab';
$string['badges'] = 'Badges';
$string['badges_sub'] = 'earned';
$string['continuelesson'] = 'Continue the lesson';
$string['dashboard'] = 'Dashboard';
$string['greeting'] = 'Hello {$a}';
$string['lessonsdonecounter'] = '{$a->done} / {$a->total} lessons completed';
$string['modulecounter'] = '{$a->done} / {$a->total} modules completed';
$string['modulepercent'] = 'Module {$a} % complete';
$string['openmodule'] = 'Open the module';
$string['programmedone'] = 'Programme completed';
$string['programmedone_title'] = 'All lessons are validated. Time for the final project.';
$string['seeparcours'] = 'See the programme';
$string['streak'] = 'Current streak';
$string['streak_sub'] = 'consecutive days';
$string['weekcounter'] = 'Week {$a->week} / {$a->weeks}';
$string['welcome'] = 'Welcome to your Alpha Trade programme.';
$string['yourparcours'] = 'Your programme';
$string['yourprogress'] = 'Your progress';

// Programme, module, lesson.
$string['editinmoodle'] = 'Edit in Moodle';
$string['evaluation'] = 'Assessment';
$string['lessoncounter'] = 'Lesson {$a->index} / {$a->count}';
$string['lessons'] = 'Lessons';
$string['modulelabel'] = 'Module {$a}';
$string['modulelocked'] = 'Module locked';
$string['modulemeta'] = '{$a->lessons} lessons · {$a->percent} %';
$string['modules'] = 'Modules';
$string['month1theme'] = 'Understand';
$string['month2theme'] = 'Analyse';
$string['month3theme'] = 'Measure';
$string['monthlabel'] = 'Month {$a}';
$string['nextlesson'] = 'Next lesson';
$string['nextmodule'] = 'Next module';
$string['objectives'] = 'Objectives';
$string['parcours'] = 'My programme';
$string['programmelead'] = 'Understand, analyse, test, measure, build: one module after another.';
$string['programmetitle'] = 'Intensive programme - 3 months';
$string['quizzes'] = 'Assessments';
$string['startevaluation'] = 'Take the assessment';

// Practice and chart analysis.
$string['analysis_bias'] = 'Bias';
$string['analysis_liquidity'] = 'Liquidity';
$string['analysis_liquidity_ph'] = 'Liquidity zones, equal highs / lows, sweeps...';
$string['analysis_reviewed'] = 'Corrected: {$a} / 100';
$string['analysis_scenario'] = 'Scenario';
$string['analysis_scenario_ph'] = 'Conditions, entry, invalidation, target.';
$string['analysis_structure'] = 'Structure';
$string['analysis_structure_ph'] = 'Trend, HH / HL, BOS, CHoCH...';
$string['analysis_submitted'] = 'Submitted';
$string['analysis_todo'] = 'To do';
$string['analysis_waiting'] = 'your analysis is waiting for the trainer\'s correction. You can still edit it.';
$string['analysisincomplete'] = 'Fill in at least the structure, the bias and the scenario.';
$string['analysisnumber'] = 'Analysis {$a}';
$string['analysissaved'] = 'Analysis submitted.';
$string['bias_bearish'] = 'Bearish';
$string['bias_bullish'] = 'Bullish';
$string['bias_neutral'] = 'Neutral';
$string['chartnotavailable'] = 'This exercise is not available.';
$string['charts_lead'] = 'Analyse a real situation, submit your reading, get the trainer\'s correction.';
$string['charttitle'] = 'Exercise title';
$string['confirmdeletechart'] = 'Delete the exercise "{$a}" and every student analysis?';
$string['correct'] = 'Correct';
$string['correctionsubtitle'] = 'Trainer analysis';
$string['correctiontitle'] = 'Alpha Trade correction';
$string['crit_partial'] = 'Partial';
$string['crit_right'] = 'Right';
$string['crit_wrong'] = 'To review';
$string['critbias'] = 'Bias';
$string['critentry'] = 'Entry';
$string['critliquidity'] = 'Liquidity';
$string['critstructure'] = 'Structure';
$string['editanalysis'] = 'Edit my analysis';
$string['filter_all'] = 'All';
$string['filter_reviewed'] = 'Corrected';
$string['filter_submitted'] = 'To correct';
$string['instructions'] = 'Instructions';
$string['invalidsymbol'] = 'Invalid symbol. Expected format: EXCHANGE:SYMBOL, for example FX:EURUSD.';
$string['newchart'] = 'New exercise';
$string['nocharts'] = 'No analysis exercise yet.';
$string['nopracticeitems'] = 'No activity available yet.';
$string['noreviews'] = 'No analysis to show.';
$string['nosignalsrule'] = 'No signals. No promises. Argue your analysis.';
$string['practice_cases'] = 'Case studies';
$string['practice_cases_desc'] = 'Make a decision and argue it.';
$string['practice_challenges'] = 'Challenges';
$string['practice_challenges_desc'] = 'Test your level.';
$string['practice_charts'] = 'Charts';
$string['practice_charts_desc'] = 'Analyse market situations.';
$string['practice_lead'] = 'Now you are no longer just watching the market. You have to analyse it.';
$string['practice_title'] = 'Now it is your turn to analyse.';
$string['practicelab'] = 'Practice Lab';
$string['referencecorrection'] = 'Reference correction';
$string['referencecorrection_help'] = 'Trainer analysis, shown to the student once their work is corrected.';
$string['reviews'] = 'Work to correct';
$string['reviews_lead'] = 'Chart analyses submitted by students.';
$string['reviewsaved'] = 'Correction saved, the student has been notified.';
$string['savereview'] = 'Save the correction';
$string['scoreout100'] = 'Score out of 100';
$string['scorerange'] = 'The score must be between 0 and 100.';
$string['seechallenges'] = 'See the challenges';
$string['sortorder'] = 'Order';
$string['student'] = 'Student';
$string['studentanalysis'] = 'Student analysis';
$string['submitanalysis'] = 'Submit my analysis';
$string['submittedon'] = 'Submitted on {$a}';
$string['symbol'] = 'TradingView symbol';
$string['symbol_help'] = 'EXCHANGE:SYMBOL format as shown on TradingView, for example FX:EURUSD, OANDA:XAUUSD, BINANCE:BTCUSDT, CAPITALCOM:US500.';
$string['timeframe'] = 'Timeframe';
$string['youranalysis'] = 'Your analysis';

// Backtesting Lab.
$string['addtrade'] = 'Add a trade';
$string['avgloss'] = 'Average loss';
$string['avgwin'] = 'Average win';
$string['backtesting'] = 'Backtesting';
$string['backtesting_lead'] = 'Test your setups on historical data. Statistics update with every trade.';
$string['backtestinglab'] = 'Backtesting Lab';
$string['candles'] = 'Candles';
$string['chartview'] = 'Chart view';
$string['confirmdeletestrategy'] = 'Delete the strategy "{$a}" and all its trades?';
$string['curve'] = 'Curve';
$string['direction'] = 'Direction';
$string['direction_long'] = 'Buy';
$string['direction_short'] = 'Sell';
$string['editstrategy'] = 'Edit the strategy';
$string['entry'] = 'Entry';
$string['equitycurve'] = 'Equity curve';
$string['expectancy'] = 'Expectancy';
$string['invalidresultr'] = 'Enter the result in R, for example 2 or -1.';
$string['market'] = 'Market';
$string['maxconsecutivelosses'] = 'Max consecutive losses';
$string['maxdrawdown'] = 'Max drawdown';
$string['minsample'] = 'No statistical edge can be claimed under {$a} trades.';
$string['mystrategies'] = 'My strategies';
$string['newstrategy'] = 'New strategy';
$string['newstrategy_lead'] = 'Define the market, the timeframe and the rules before entering the first trade.';
$string['nostrategies'] = 'No strategy yet. Start by defining your rules.';
$string['notes'] = 'Notes';
$string['notrades'] = 'No trade entered yet.';
$string['period'] = 'Tested period';
$string['period_ph'] = 'E.g. January 2023 to June 2025';
$string['profitfactor'] = 'Profit factor';
$string['resultr'] = 'Result (R)';
$string['resultr_help'] = 'Result expressed as a multiple of the risk: +2 for a gain of twice the risk, -1 for a stop hit, 0 for breakeven.';
$string['savestrategy'] = 'Save the strategy';
$string['savetrade'] = 'Save';
$string['seereport'] = 'See my report';
$string['seetrades'] = 'See the trades';
$string['statistics'] = 'Statistics';
$string['stoploss'] = 'Stop loss';
$string['stoploss_short'] = 'SL';
$string['strategy'] = 'Strategy';
$string['strategycreated'] = 'Strategy created. Add your historical trades.';
$string['strategyname'] = 'Strategy name';
$string['strategyname_ph'] = 'E.g. Alpha Setup #01';
$string['strategyrules'] = 'Strategy rules';
$string['takeprofit'] = 'Take profit';
$string['takeprofit_short'] = 'TP';
$string['totalr'] = 'Total result';
$string['tradedate'] = 'Trade date';
$string['trades'] = 'Trades';
$string['tradesaved'] = 'Trade saved.';
$string['tradeslist'] = 'Trades';
$string['verdict'] = 'Verdict';
$string['verdict_negative'] = 'Expectancy is negative on the tested sample: the strategy shows no statistical edge as it stands.';
$string['verdict_positive'] = 'Your strategy shows a positive expectancy on the tested sample.';
$string['verdict_small'] = 'The current sample is not large enough to demonstrate a statistical edge.';
$string['winrate'] = 'Win rate';

// Trading journal.
$string['asset'] = 'Asset';
$string['assetclass'] = 'Asset class';
$string['assetclass_crypto'] = 'Crypto';
$string['assetclass_forex'] = 'Forex';
$string['assetclass_gold'] = 'Gold';
$string['assetclass_indices'] = 'Indices';
$string['assetclass_other'] = 'Other';
$string['assetclass_stocks'] = 'Stocks';
$string['confirmdeletetrade'] = 'Delete this trade from the journal?';
$string['edittrade'] = 'Edit the trade';
$string['emotion'] = 'Emotional state';
$string['emotion_all'] = 'All emotions';
$string['emotion_calm'] = 'Calm';
$string['emotion_confident'] = 'Confident';
$string['emotion_euphoric'] = 'Euphoric';
$string['emotion_frustrated'] = 'Frustrated';
$string['emotion_hesitant'] = 'Hesitant';
$string['emotion_stressed'] = 'Stressed';
$string['journal'] = 'Trading journal';
$string['journal_after'] = 'After the trade';
$string['journal_before'] = 'Before the trade';
$string['journal_context'] = 'What was the context?';
$string['journal_followedplan'] = 'Did I follow my plan?';
$string['journal_form_lead'] = 'One trade, before and after: this is where psychology meets numbers.';
$string['journal_lead'] = 'Every trade documented: why, how, and what you learn from it.';
$string['journal_reason'] = 'Why did I enter?';
$string['journal_review'] = 'Review';
$string['journal_title'] = 'Trading journal';
$string['journal_trade'] = 'The trade';
$string['journalentries'] = 'Journal trades';
$string['nojournal'] = 'No matching trade. Add your first trade to the journal.';
$string['period_all'] = 'All dates';
$string['period_month'] = 'This month';
$string['period_week'] = 'This week';
$string['planfollowed'] = 'Plan followed';
$string['plannotfollowed'] = 'Plan not followed';
$string['result_all'] = 'All results';
$string['result_loss'] = 'Losses';
$string['result_win'] = 'Wins';
$string['riskpct'] = 'Risk (%)';
$string['screenshot'] = 'Screenshot';
$string['setup'] = 'Setup';
$string['setup_ph'] = 'E.g. Sweep + BOS';
$string['weeksummary'] = 'This week - {$a->trades} trades · {$a->r} · {$a->winrate} % win rate';

// Tools.
$string['breakevenwinrate'] = 'Break-even win rate';
$string['calculate'] = 'Calculate';
$string['calculator_empty'] = 'Fill in the fields to get the result.';
$string['invalidstop'] = 'The stop must differ from the entry and sit on the right side of the trade.';
$string['positionsize'] = 'Position size';
$string['riskamount'] = 'Amount at risk (1R)';
$string['riskwarning'] = 'More than 2 % risk per trade: check it is consistent with your plan.';
$string['rrratio'] = 'Risk / reward ratio';
$string['stopdistance'] = 'Stop distance';
$string['tool_field_capital'] = 'Capital';
$string['tool_field_entry'] = 'Entry price';
$string['tool_field_pointvalue'] = 'Value of one point for 1 unit';
$string['tool_field_pointvalue_help'] = 'Gain or loss for a 1-point price move with one unit (1 for stocks, pip value x 10,000 for a forex lot...).';
$string['tool_field_riskpct'] = 'Risk per trade (%)';
$string['tool_field_stoploss'] = 'Stop loss';
$string['tool_field_takeprofit'] = 'Target (take profit)';
$string['tool_position'] = 'Position size';
$string['tool_position_desc'] = 'Position size from the risk and the stop distance.';
$string['tool_risk'] = 'Risk calculator';
$string['tool_risk_desc'] = 'Amount at risk per trade based on your capital.';
$string['tool_rr'] = 'Risk / Reward';
$string['tool_rr_desc'] = 'Risk / reward ratio and minimum win rate to break even.';
$string['tool_stats'] = 'Statistics';
$string['tool_stats_desc'] = 'Statistics of your strategies in the Backtesting Lab.';
$string['tools'] = 'Tools';
$string['tools_lead'] = 'The basic calculations before every trade.';
$string['tools_title'] = 'Alpha Trade tools';

// Resources.
$string['noresources'] = 'No matching resource.';
$string['resources'] = 'Resources';
$string['resources_lead'] = 'Sheets, checklists, templates and guides of the programme.';
$string['resources_title'] = 'Alpha Trade library';
$string['resourcesnotconfigured'] = 'The library is not configured yet.';
$string['restype_checklist'] = 'Checklist';
$string['restype_pdf'] = 'PDF';
$string['restype_template'] = 'Template';
$string['restype_video'] = 'Video';
$string['restypes_checklist'] = 'Checklists';
$string['restypes_pdf'] = 'PDF';
$string['restypes_template'] = 'Templates';
$string['restypes_video'] = 'Videos';
$string['searchresources'] = 'Search a resource';

// Community.
$string['community'] = 'Community';
$string['community_analyses'] = 'Market analyses';
$string['community_analyses_desc'] = 'Share argued analyses and discuss them.';
$string['community_announcements'] = 'Alpha Trade announcements';
$string['community_announcements_desc'] = 'Official information from the trainers.';
$string['community_lead'] = 'Three spaces, no more. Exchange, ask, argue.';
$string['community_qa'] = 'Questions and answers';
$string['community_qa_desc'] = 'Ask your questions about the programme and its notions.';
$string['community_title'] = 'The Alpha Trade community';
$string['discussioncount'] = '{$a} discussions';
$string['nocommunity'] = 'Community spaces are not configured yet.';
$string['nosignals'] = 'Alpha Trade does not provide trading signals.';

// Profile, final project, certification.
$string['alphatradingsystem'] = 'Alpha Trading System';
$string['certification'] = 'Certification';
$string['certification_eyebrow'] = 'Alpha Trade - End of programme certification';
$string['certification_lead'] = 'The conditions to earn your certificate.';
$string['certification_locked'] = 'The certificate unlocks once every condition is met.';
$string['certification_mention'] = 'has completed the intensive programme: Investing · ICT/SMC analysis · Quantitative trading.';
$string['cond_backtest'] = 'Backtesting done';
$string['cond_challenges'] = 'Challenges completed';
$string['cond_lessons'] = 'Lessons completed';
$string['cond_project'] = 'Final project validated';
$string['cond_quizzes'] = 'Quizzes passed';
$string['conditions'] = 'Conditions';
$string['continueproject'] = 'Continue my project';
$string['downloadcertificate'] = 'Download my certificate';
$string['finalproject'] = 'Final project';
$string['finalproject_lead'] = 'Build, test and document your own method.';
$string['nobadges'] = 'No badge is defined for the programme yet.';
$string['noresults'] = 'No backtested strategy yet.';
$string['nosteps'] = 'The project steps are not published yet.';
$string['profile'] = 'Profile and progress';
$string['projectprogress'] = 'of the project done';
$string['steps'] = 'Steps';
$string['tradesof'] = '{$a->done} / {$a->min} trades';
$string['training'] = 'Training';
$string['yourresults'] = 'Your results';

// Trainer space.
$string['alert_inactive'] = 'No activity for {$a} days';
$string['alert_neveraccessed'] = 'Has never opened the programme';
$string['alert_notstarted'] = '"{$a->done}" completed, "{$a->next}" not started yet';
$string['alert_quizfailures'] = '{$a->count} failures on "{$a->quiz}"';
$string['currentmodule'] = 'Current module';
$string['kpi_active'] = 'Active this week';
$string['kpi_blocked'] = 'Blocked students';
$string['kpi_enrolled'] = 'Enrolled';
$string['kpi_late'] = 'Behind schedule';
$string['kpi_tograde'] = 'Work to correct';
$string['noalerts'] = 'No pedagogical alert: every student is progressing.';
$string['nostrategiesstudents'] = 'No student has created a strategy yet.';
$string['nostudents'] = 'No student enrolled in the programme.';
$string['pedagogicalalerts'] = 'Pedagogical alerts';
$string['sampleok'] = 'Sample large enough';
$string['samplesmall'] = 'Sample too small';
$string['student_blocked'] = 'Blocked';
$string['student_late'] = 'Behind';
$string['student_ok'] = 'On track';
$string['students'] = 'Students';
$string['teacherdashboard'] = 'Trainer dashboard';
$string['teacherspace'] = 'Trainer space';
$string['tradesminimum'] = 'Minimum expected for certification: {$a} trades per strategy.';
