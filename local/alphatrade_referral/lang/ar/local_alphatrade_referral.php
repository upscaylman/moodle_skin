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
 * Arabic strings for local_alphatrade_referral.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'إحالة ألفا تريد';
$string['manage'] = 'إدارة الإحالة';
$string['settings'] = 'الإعدادات';
$string['dashboard'] = 'الإحالة';
$string['dashboard_sub'] = 'رابطك، مدعووك، مكافآتك.';
$string['disabled'] = 'برنامج الإحالة متوقف حاليا.';
$string['yourlink'] = 'رابط الإحالة الخاص بك';
$string['yourlink_help'] = 'شارك هذا الرابط. من ينشئ حسابا بعد فتحه يصبح مدعوك، نهائيا.';
$string['copy'] = 'نسخ';
$string['copied'] = 'تم النسخ';
$string['kpi_clicks'] = 'النقرات';
$string['kpi_referrals'] = 'المدعوون';
$string['kpi_conversions'] = 'التحويلات';
$string['kpi_earned'] = 'المكتسب';
$string['pending_note'] = '{$a->amount} في الانتظار: تعتمد المكافأة بعد {$a->days} يوما من التحويل.';
$string['myreferrals'] = 'مدعووي';
$string['myrewards'] = 'مكافآتي';
$string['referral'] = 'المدعو';
$string['referrer'] = 'الداعي';
$string['status'] = 'الحالة';
$string['since'] = 'منذ';
$string['amount'] = 'المبلغ';
$string['availableon'] = 'متاحة في';
$string['noreferrals'] = 'لا يوجد مدعو بعد. شارك رابطك للبدء.';
$string['norewards'] = 'لا توجد مكافأة بعد.';
$string['discord'] = 'ديسكورد';
$string['discord_help'] = 'اربط حساب ديسكورد لتصلك إشعارات الإحالة هناك.';
$string['discord_link'] = 'ربط حساب ديسكورد';
$string['discord_linked'] = 'مرتبط بـ {$a}.';
$string['discord_unlink'] = 'إلغاء الربط';
$string['discord_notconfigured'] = 'ديسكورد غير مهيأ في هذا الموقع.';
$string['discord_unlinked'] = 'تم إلغاء ربط حساب ديسكورد. سجل الإحالة لديك كما هو.';
$string['discord_failed'] = 'فشل ربط ديسكورد. أعد المحاولة.';
$string['discord_done'] = 'تم ربط حساب ديسكورد.';
$string['relation_pending'] = 'في الانتظار';
$string['relation_active'] = 'نشط';
$string['relation_converted'] = 'محول';
$string['relation_rewarded'] = 'تمت مكافأته';
$string['relation_cancelled'] = 'ملغى';
$string['relation_blocked'] = 'محظور';
$string['reward_pending'] = 'في الانتظار';
$string['reward_approved'] = 'معتمدة';
$string['reward_paid'] = 'مدفوعة';
$string['reward_cancelled'] = 'ملغاة';
$string['reward_reversed'] = 'معكوسة';
$string['task_approve'] = 'اعتماد مكافآت الإحالة المستحقة';
$string['task_notify'] = 'إرسال إشعارات الإحالة المنتظرة في ديسكورد';
$string['notify_conversion'] = 'أحد مدعويك سجل للتو. تجري مراجعة المكافأة.';
$string['notify_reward'] = 'تمت الموافقة على مكافأة إحالتك البالغة {$a}.';
$string['reviewqueue'] = 'قائمة المراجعة اليدوية';
$string['noreview'] = 'لا شيء للمراجعة.';
$string['signals'] = 'الإشارات';
$string['score'] = 'النتيجة';
$string['decision'] = 'القرار';
$string['reason'] = 'السبب';
$string['approve'] = 'اعتماد';
$string['block'] = 'حظر';
$string['reasonrequired'] = 'السبب إلزامي: يسجل مع اسمك والتاريخ.';
$string['decisionsaved'] = 'تم تسجيل القرار.';
$string['lastreferrals'] = 'آخر الإحالات';
$string['norelations'] = 'لا توجد إحالة مسجلة.';
$string['conversions'] = 'التحويلات';
$string['queuestate'] = 'قائمة ديسكورد: {$a->pending} في الانتظار، {$a->failed} فاشلة';
$string['kpi_codes'] = 'الرموز النشطة';
$string['kpi_relations'] = 'الإحالات';
$string['kpi_approved'] = 'التحويلات المعتمدة';
$string['kpi_review'] = 'قيد المراجعة';
$string['settings_programme'] = 'البرنامج';
$string['settings_programme_desc'] = 'مودل هو مصدر الحقيقة: الإسناد والتحويلات والمكافآت كلها هنا.';
$string['settings_fraud'] = 'مكافحة الاحتيال';
$string['settings_fraud_desc'] = 'الإشارة الواحدة لا تقرر وحدها: تضيف نقاطا. فوق العتبة يذهب التحويل إلى مراجعة يدوية وتجمد المكافأة دون إلغائها.';
$string['settings_discord'] = 'ديسكورد';
$string['settings_discord_desc'] = 'ديسكورد واجهة، لا مصدر بيانات. رابط العودة المطلوب تصريحه في تطبيق ديسكورد: {$a}';
$string['set_enabled'] = 'تفعيل البرنامج';
$string['set_enabled_desc'] = 'عند التوقف: لا تسجل نقرات ولا إسناد ولا تحويلات ولا مكافآت.';
$string['set_cookiedays'] = 'مدة كعكة الإسناد (أيام)';
$string['set_cookiedays_desc'] = 'كم تحتفظ النقرة بالداعي قبل إنشاء الحساب.';
$string['set_trigger'] = 'التحويل هو';
$string['set_trigger_desc'] = 'ما يحول المدعو إلى تحويل. تنصح البنية بالدفع المؤكد مع مهلة التحقق.';
$string['trigger_payment'] = 'دفع مؤكد';
$string['trigger_enrolment'] = 'تسجيل في تكوين';
$string['trigger_both'] = 'أي منهما';
$string['set_rewardtype'] = 'نوع المكافأة';
$string['set_rewardtype_desc'] = 'مبلغ ثابت أو نسبة من المبلغ المدفوع.';
$string['reward_fixed'] = 'مبلغ ثابت';
$string['reward_percent'] = 'نسبة مئوية';
$string['set_rewardamount'] = 'المبلغ الثابت';
$string['set_rewardamount_desc'] = 'يستعمل عندما تكون المكافأة مبلغا ثابتا.';
$string['set_rewardpercent'] = 'النسبة المئوية';
$string['set_rewardpercent_desc'] = 'يستعمل عندما تكون المكافأة نسبة من المبلغ المدفوع.';
$string['set_currency'] = 'العملة';
$string['set_currency_desc'] = 'رمز ISO، مثال EUR أو TND.';
$string['set_validationdays'] = 'مهلة التحقق (أيام)';
$string['set_validationdays_desc'] = 'لا أحد يدفع له فورا: المهلة تغطي نافذة الاسترجاع. تبقى المكافأة في الانتظار حتى ذلك الحين.';
$string['set_landing'] = 'صفحة الوصول';
$string['set_landing_desc'] = 'إلى أين يرسل رابط الإحالة الزائر، نسبة إلى جذر الموقع.';
$string['set_shorturl'] = 'رابط قصير ‎/r/CODE';
$string['set_shorturl_desc'] = 'فقط بعد أن يعيد خادم الويب كتابة ‎/r/‎ نحو هذه الإضافة. وإلا يستعمل الرابط الطويل، وهو يعمل دائما.';
$string['set_threshold'] = 'عتبة المراجعة';
$string['set_threshold_desc'] = 'نتيجة الاحتيال التي يذهب فوقها التحويل إلى مراجعة يدوية.';
$string['set_ratelimit'] = 'النقرات في الدقيقة لكل عنوان';
$string['set_ratelimit_desc'] = 'يحمي ‎/r/CODE‎ من تخمين الرموز. القيمة 0 تعطل الحد.';
$string['set_apisecret'] = 'سر الويب هوك';
$string['set_apisecret_desc'] = 'رمز Bearer الذي ينتظره webhook.php. متغير البيئة REFERRAL_API_SECRET له الأولوية.';
$string['set_discordnotify'] = 'إشعارات ديسكورد';
$string['set_discordnotify_desc'] = 'توضع في قائمة ويعيدها الكرون: ديسكورد لا يعطل شيئا أبدا.';
$string['set_clientid'] = 'معرف التطبيق';
$string['set_clientid_desc'] = 'تطبيق ديسكورد. DISCORD_CLIENT_ID له الأولوية.';
$string['set_clientsecret'] = 'سر التطبيق';
$string['set_clientsecret_desc'] = 'DISCORD_CLIENT_SECRET له الأولوية.';
$string['set_bottoken'] = 'رمز البوت';
$string['set_bottoken_desc'] = 'يستعمل لإرسال الرسائل الخاصة. DISCORD_BOT_TOKEN له الأولوية.';
$string['set_guildid'] = 'معرف الخادم';
$string['set_guildid_desc'] = 'خادم ديسكورد الخاص بالمجتمع. DISCORD_GUILD_ID له الأولوية.';
$string['set_rolesponsor'] = 'التحويلات لدور الداعي';
$string['set_rolesponsor_desc'] = 'القيمة 0 تعطل هذا الدور.';
$string['set_roleambassador'] = 'التحويلات لدور السفير';
$string['set_roleambassador_desc'] = 'القيمة 0 تعطل هذا الدور.';
$string['set_rolesuper'] = 'التحويلات لدور الداعي الأكبر';
$string['set_rolesuper_desc'] = 'القيمة 0 تعطل هذا الدور.';
$string['alphatrade_referral:view'] = 'الاطلاع على كل الإحالات';
$string['alphatrade_referral:viewown'] = 'الاطلاع على إحالتي';
$string['alphatrade_referral:manage'] = 'إدارة الرموز والروابط';
$string['alphatrade_referral:validate'] = 'البت في قائمة المراجعة';
$string['alphatrade_referral:configure'] = 'تهيئة البرنامج';
$string['privacy:metadata:codes'] = 'رمز الإحالة الخاص بالمستخدم.';
$string['privacy:metadata:codes:code'] = 'الرمز نفسه.';
$string['privacy:metadata:codes:status'] = 'ما إذا كان الرمز نشطا.';
$string['privacy:metadata:codes:timecreated'] = 'وقت إنشاء الرمز.';
$string['privacy:metadata:relations'] = 'من دعا من. الإسناد نهائي.';
$string['privacy:metadata:relations:referrerid'] = 'الداعي.';
$string['privacy:metadata:relations:referredid'] = 'المدعو.';
$string['privacy:metadata:relations:status'] = 'حالة العلاقة.';
$string['privacy:metadata:conversions'] = 'التحويلات التجارية المرتبطة بإحالة.';
$string['privacy:metadata:conversions:amount'] = 'المبلغ المدفوع.';
$string['privacy:metadata:conversions:status'] = 'حالة التحويل.';
$string['privacy:metadata:rewards'] = 'المكافآت المستحقة للداعي.';
$string['privacy:metadata:rewards:amount'] = 'مبلغ المكافأة.';
$string['privacy:metadata:rewards:status'] = 'حالة المكافأة.';
$string['privacy:metadata:discord'] = 'الربط بين حساب مودل وحساب ديسكورد.';
$string['privacy:metadata:discord:discorduserid'] = 'معرف حساب ديسكورد.';
$string['privacy:metadata:discord:timelinked'] = 'وقت ربط الحسابين.';
$string['privacy:metadata:discordapi'] = 'الرسائل الخاصة المرسلة إلى العضو عبر واجهة ديسكورد.';
$string['privacy:metadata:discordapi:discorduserid'] = 'الحساب المستلم.';
$string['privacy:metadata:discordapi:content'] = 'إشعار محايد: الحالة والمبلغ، لا شيء آخر.';
$string['notlinked'] = 'حساب ديسكورد هذا غير مرتبط بأي حساب ألفا تريد.';
