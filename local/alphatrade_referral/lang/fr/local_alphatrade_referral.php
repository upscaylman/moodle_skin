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
 * French strings for local_alphatrade_referral.
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'Parrainage Alpha Trade';
$string['manage'] = 'Administration du parrainage';
$string['settings'] = 'Réglages';
$string['dashboard'] = 'Parrainage';
$string['dashboard_sub'] = 'Votre lien, vos filleuls, vos récompenses.';
$string['disabled'] = 'Le programme de parrainage est désactivé.';
$string['yourlink'] = 'Votre lien de parrainage';
$string['yourlink_help'] = 'Partagez ce lien. Qui crée un compte après l\'avoir suivi devient votre filleul, une fois pour toutes.';
$string['copy'] = 'Copier';
$string['copied'] = 'Copié';
$string['kpi_clicks'] = 'Clics';
$string['kpi_referrals'] = 'Filleuls';
$string['kpi_conversions'] = 'Conversions';
$string['kpi_earned'] = 'Gagné';
$string['pending_note'] = '{$a->amount} en attente : une récompense est approuvée {$a->days} jours après la conversion.';
$string['myreferrals'] = 'Mes filleuls';
$string['myrewards'] = 'Mes récompenses';
$string['referral'] = 'Filleul';
$string['referrer'] = 'Parrain';
$string['status'] = 'Statut';
$string['since'] = 'Depuis';
$string['amount'] = 'Montant';
$string['availableon'] = 'Disponible le';
$string['noreferrals'] = 'Aucun filleul pour le moment. Partagez votre lien pour commencer.';
$string['norewards'] = 'Aucune récompense pour le moment.';
$string['discord'] = 'Discord';
$string['discord_help'] = 'Liez votre compte Discord pour y recevoir vos notifications de parrainage.';
$string['discord_link'] = 'Lier mon compte Discord';
$string['discord_linked'] = 'Lié à {$a}.';
$string['discord_unlink'] = 'Délier';
$string['discord_notconfigured'] = 'Discord n\'est pas configuré sur ce site.';
$string['discord_unlinked'] = 'Compte Discord délié. Votre historique de parrainage est intact.';
$string['discord_failed'] = 'La liaison Discord a échoué. Réessayez.';
$string['discord_done'] = 'Compte Discord lié.';
$string['relation_pending'] = 'En attente';
$string['relation_active'] = 'Actif';
$string['relation_converted'] = 'Converti';
$string['relation_rewarded'] = 'Récompensé';
$string['relation_cancelled'] = 'Annulé';
$string['relation_blocked'] = 'Bloqué';
$string['reward_pending'] = 'En attente';
$string['reward_approved'] = 'Approuvée';
$string['reward_paid'] = 'Versée';
$string['reward_cancelled'] = 'Annulée';
$string['reward_reversed'] = 'Contre-passée';
$string['task_approve'] = 'Approuver les récompenses de parrainage échues';
$string['task_notify'] = 'Envoyer les notifications Discord de parrainage en file';
$string['notify_conversion'] = 'Un de vos filleuls vient de s\'inscrire. La récompense est en cours de vérification.';
$string['notify_reward'] = 'Votre récompense de parrainage de {$a} a été approuvée.';
$string['reviewqueue'] = 'File de revue manuelle';
$string['noreview'] = 'Rien à examiner.';
$string['signals'] = 'Signaux';
$string['score'] = 'Score';
$string['decision'] = 'Décision';
$string['reason'] = 'Motif';
$string['approve'] = 'Valider';
$string['block'] = 'Bloquer';
$string['reasonrequired'] = 'Un motif est obligatoire : il est enregistré avec votre nom et la date.';
$string['decisionsaved'] = 'Décision enregistrée.';
$string['lastreferrals'] = 'Derniers parrainages';
$string['norelations'] = 'Aucun parrainage enregistré.';
$string['conversions'] = 'Conversions';
$string['queuestate'] = 'File Discord : {$a->pending} en attente, {$a->failed} en échec';
$string['kpi_codes'] = 'Codes actifs';
$string['kpi_relations'] = 'Parrainages';
$string['kpi_approved'] = 'Conversions approuvées';
$string['kpi_review'] = 'En revue';
$string['settings_programme'] = 'Programme';
$string['settings_programme_desc'] = 'Moodle est la source de vérité : attribution, conversions et récompenses vivent ici.';
$string['settings_fraud'] = 'Anti-fraude';
$string['settings_fraud_desc'] = 'Un signal ne décide jamais seul : il ajoute un score. Au-dessus du seuil, la conversion part en revue manuelle et la récompense est gelée, jamais annulée.';
$string['settings_discord'] = 'Discord';
$string['settings_discord_desc'] = 'Discord est une interface, jamais une source de données. URL de retour à déclarer dans l\'application Discord : {$a}';
$string['set_enabled'] = 'Activer le programme';
$string['set_enabled_desc'] = 'Désactivé : aucun clic, attribution, conversion ni récompense n\'est enregistré.';
$string['set_cookiedays'] = 'Durée du cookie d\'attribution (jours)';
$string['set_cookiedays_desc'] = 'Combien de temps un clic garde le parrain, avant la création du compte.';
$string['set_trigger'] = 'Une conversion, c\'est';
$string['set_trigger_desc'] = 'Ce qui transforme un filleul en conversion. L\'architecture recommande le paiement confirmé, plus le délai de validation.';
$string['trigger_payment'] = 'Un paiement confirmé';
$string['trigger_enrolment'] = 'Une inscription à une formation';
$string['trigger_both'] = 'L\'un ou l\'autre';
$string['set_rewardtype'] = 'Type de récompense';
$string['set_rewardtype_desc'] = 'Montant fixe, ou pourcentage du montant payé.';
$string['reward_fixed'] = 'Montant fixe';
$string['reward_percent'] = 'Pourcentage';
$string['set_rewardamount'] = 'Montant fixe';
$string['set_rewardamount_desc'] = 'Utilisé quand la récompense est un montant fixe.';
$string['set_rewardpercent'] = 'Pourcentage';
$string['set_rewardpercent_desc'] = 'Utilisé quand la récompense est un pourcentage du montant payé.';
$string['set_currency'] = 'Devise';
$string['set_currency_desc'] = 'Code ISO, par exemple EUR ou TND.';
$string['set_validationdays'] = 'Délai de validation (jours)';
$string['set_validationdays_desc'] = 'Personne n\'est payé immédiatement : le délai couvre la fenêtre de remboursement. La récompense reste en attente jusque-là.';
$string['set_landing'] = 'Page d\'arrivée';
$string['set_landing_desc'] = 'Où un lien de parrainage envoie le visiteur, relativement à la racine du site.';
$string['set_shorturl'] = 'Lien court /r/CODE';
$string['set_shorturl_desc'] = 'Seulement une fois que le serveur web réécrit /r/ vers ce plugin. Sinon le lien long est utilisé, et il marche toujours.';
$string['set_threshold'] = 'Seuil de revue';
$string['set_threshold_desc'] = 'Score de fraude au-dessus duquel une conversion part en revue manuelle.';
$string['set_ratelimit'] = 'Clics par minute et par IP';
$string['set_ratelimit_desc'] = 'Protège /r/CODE contre le balayage de codes. 0 désactive la limite.';
$string['set_apisecret'] = 'Secret du webhook';
$string['set_apisecret_desc'] = 'Jeton Bearer attendu par webhook.php. La variable d\'environnement REFERRAL_API_SECRET a la priorité.';
$string['set_discordnotify'] = 'Notifications Discord';
$string['set_discordnotify_desc'] = 'Mises en file et rejouées par le cron : Discord n\'est jamais bloquant.';
$string['set_clientid'] = 'Client ID';
$string['set_clientid_desc'] = 'Application Discord. DISCORD_CLIENT_ID a la priorité.';
$string['set_clientsecret'] = 'Client secret';
$string['set_clientsecret_desc'] = 'DISCORD_CLIENT_SECRET a la priorité.';
$string['set_bottoken'] = 'Jeton du bot';
$string['set_bottoken_desc'] = 'Sert à envoyer les messages privés. DISCORD_BOT_TOKEN a la priorité.';
$string['set_guildid'] = 'ID du serveur';
$string['set_guildid_desc'] = 'Serveur Discord de la communauté. DISCORD_GUILD_ID a la priorité.';
$string['set_rolesponsor'] = 'Conversions pour le rôle Parrain';
$string['set_rolesponsor_desc'] = '0 désactive ce rôle.';
$string['set_roleambassador'] = 'Conversions pour le rôle Ambassadeur';
$string['set_roleambassador_desc'] = '0 désactive ce rôle.';
$string['set_rolesuper'] = 'Conversions pour le rôle Super Parrain';
$string['set_rolesuper_desc'] = '0 désactive ce rôle.';
$string['alphatrade_referral:view'] = 'Voir tous les parrainages';
$string['alphatrade_referral:viewown'] = 'Voir mon propre parrainage';
$string['alphatrade_referral:manage'] = 'Gérer les codes et les liaisons';
$string['alphatrade_referral:validate'] = 'Trancher la file de revue';
$string['alphatrade_referral:configure'] = 'Configurer le programme';
$string['privacy:metadata:codes'] = 'Le code de parrainage d\'un utilisateur.';
$string['privacy:metadata:codes:code'] = 'Le code lui-même.';
$string['privacy:metadata:codes:status'] = 'Si le code est actif.';
$string['privacy:metadata:codes:timecreated'] = 'Quand le code a été créé.';
$string['privacy:metadata:relations'] = 'Qui a parrainé qui. L\'attribution est définitive.';
$string['privacy:metadata:relations:referrerid'] = 'Le parrain.';
$string['privacy:metadata:relations:referredid'] = 'Le filleul.';
$string['privacy:metadata:relations:status'] = 'État de la relation.';
$string['privacy:metadata:conversions'] = 'Les conversions commerciales rattachées à un parrainage.';
$string['privacy:metadata:conversions:amount'] = 'Montant payé.';
$string['privacy:metadata:conversions:status'] = 'État de la conversion.';
$string['privacy:metadata:rewards'] = 'Les récompenses dues à un parrain.';
$string['privacy:metadata:rewards:amount'] = 'Montant de la récompense.';
$string['privacy:metadata:rewards:status'] = 'État de la récompense.';
$string['privacy:metadata:discord'] = 'La liaison entre un compte Moodle et un compte Discord.';
$string['privacy:metadata:discord:discorduserid'] = 'L\'identifiant du compte Discord.';
$string['privacy:metadata:discord:timelinked'] = 'Quand les comptes ont été liés.';
$string['privacy:metadata:discordapi'] = 'Les messages privés envoyés au membre via l\'API Discord.';
$string['privacy:metadata:discordapi:discorduserid'] = 'Le compte destinataire.';
$string['privacy:metadata:discordapi:content'] = 'Une notification neutre : statut et montant, rien d\'autre.';
$string['notlinked'] = 'Ce compte Discord n\'est lié à aucun compte Alpha Trade.';
