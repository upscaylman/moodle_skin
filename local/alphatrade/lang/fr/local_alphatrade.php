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
 * Chaînes françaises de l'application Alpha Trade.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Alpha Trade';

// Capacités, messages, vie privée.
$string['local/alphatrade:managecharts'] = 'Créer et modifier les exercices d\'analyse de graphique';
$string['local/alphatrade:reviewanalysis'] = 'Corriger les analyses de graphique';
$string['local/alphatrade:viewstudentdata'] = 'Voir les backtests, journaux et analyses des étudiants';
$string['local/alphatrade:viewteacher'] = 'Accéder à l\'espace formateur';
$string['messageprovider:analysisreviewed'] = 'Analyse de graphique corrigée';
$string['notification_reviewed_body'] = 'Votre analyse "{$a->chart}" a été corrigée : {$a->score} / 100. Voir la correction : {$a->url}';
$string['notification_reviewed_subject'] = 'Correction disponible : {$a}';
$string['privacy:metadata:asset'] = 'Actif tradé';
$string['privacy:metadata:assetclass'] = 'Classe d\'actif';
$string['privacy:metadata:bias'] = 'Biais retenu dans l\'analyse';
$string['privacy:metadata:chartid'] = 'Exercice d\'analyse concerné';
$string['privacy:metadata:core_files'] = 'Captures d\'écran jointes aux trades du journal';
$string['privacy:metadata:core_message'] = 'Notifications envoyées quand une analyse est corrigée';
$string['privacy:metadata:description'] = 'Règles de la stratégie';
$string['privacy:metadata:direction'] = 'Sens du trade (achat ou vente)';
$string['privacy:metadata:emotion'] = 'État émotionnel déclaré';
$string['privacy:metadata:entry'] = 'Prix d\'entrée';
$string['privacy:metadata:feedback'] = 'Commentaire du formateur';
$string['privacy:metadata:followedplan'] = 'Plan de trading respecté ou non';
$string['privacy:metadata:liquidity'] = 'Analyse de la liquidité';
$string['privacy:metadata:local_alphatrade_analysis'] = 'Analyses de graphique soumises par l\'étudiant';
$string['privacy:metadata:local_alphatrade_bttrade'] = 'Trades historiques saisis dans le Backtesting Lab';
$string['privacy:metadata:local_alphatrade_journal'] = 'Entrées du journal de trading';
$string['privacy:metadata:local_alphatrade_strategy'] = 'Stratégies du Backtesting Lab';
$string['privacy:metadata:market'] = 'Marché (symbole)';
$string['privacy:metadata:marketcontext'] = 'Contexte de marché avant le trade';
$string['privacy:metadata:name'] = 'Nom de la stratégie';
$string['privacy:metadata:notes'] = 'Notes sur le trade';
$string['privacy:metadata:period'] = 'Période testée';
$string['privacy:metadata:reason'] = 'Raison de l\'entrée';
$string['privacy:metadata:resultr'] = 'Résultat en R';
$string['privacy:metadata:review'] = 'Bilan après le trade';
$string['privacy:metadata:riskpct'] = 'Risque en pourcentage du capital';
$string['privacy:metadata:scenario'] = 'Scénario proposé';
$string['privacy:metadata:score'] = 'Note sur 100';
$string['privacy:metadata:setup'] = 'Setup utilisé';
$string['privacy:metadata:status'] = 'Statut de la correction';
$string['privacy:metadata:stoploss'] = 'Stop loss';
$string['privacy:metadata:strategyid'] = 'Stratégie du trade';
$string['privacy:metadata:structure'] = 'Analyse de la structure';
$string['privacy:metadata:takeprofit'] = 'Take profit';
$string['privacy:metadata:timecreated'] = 'Date de création';
$string['privacy:metadata:timeframe'] = 'Unité de temps';
$string['privacy:metadata:timemodified'] = 'Date de modification';
$string['privacy:metadata:timereviewed'] = 'Date de correction';
$string['privacy:metadata:tradedate'] = 'Date du trade';
$string['privacy:metadata:userid'] = 'Étudiant propriétaire des données';

// Réglages.
$string['analysisforum'] = 'Forum "Analyses de marché"';
$string['casessection'] = 'Section des cas pratiques (numéro)';
$string['certificatecm'] = 'Activité certificat (identifiant cmid)';
$string['certificatecm_desc'] = 'Identifiant de l\'activité qui délivre le certificat (par exemple mod_customcert). 0 : aucun.';
$string['challengessection'] = 'Section des challenges (numéro)';
$string['inactivedays'] = 'Alerte d\'inactivité (jours)';
$string['inactivedays_desc'] = 'Au-delà, l\'étudiant apparaît comme bloqué dans l\'espace formateur.';
$string['minbacktesttrades'] = 'Nombre minimum de trades backtestés (certification)';
$string['monthsplit'] = 'Modules par mois';
$string['monthsplit_desc'] = 'Nombre de modules dans chaque mois, séparés par des virgules. Exemple : 3,4,5.';
$string['practicecourse'] = 'Cours "Pratique"';
$string['practicecourse_desc'] = 'Cours qui contient les cas pratiques et les challenges (une section chacun).';
$string['programmecourse'] = 'Cours du programme';
$string['programmecourse_desc'] = 'Le parcours de 3 mois : chaque section est un module, chaque activité une leçon, les tests sont les évaluations. Activer le suivi d\'achèvement.';
$string['programmeweeks'] = 'Durée du programme (semaines)';
$string['projectsection'] = 'Section du projet final (numéro)';
$string['projectsection_desc'] = 'Section qui contient les étapes de l\'Alpha Trading System. 0 : dernier module.';
$string['qaforum'] = 'Forum "Questions et réponses"';
$string['redirectcourse'] = 'Rediriger le cours du programme';
$string['redirectcourse_desc'] = 'Les étudiants qui ouvrent la page du cours arrivent sur "Mon parcours" (les formateurs gardent la page Moodle).';
$string['redirectdashboard'] = 'Rediriger le tableau de bord Moodle';
$string['redirectdashboard_desc'] = 'La page /my/ ouvre le tableau de bord Alpha Trade (sauf pour les administrateurs).';
$string['resourcescourse'] = 'Cours "Ressources"';
$string['resourcescourse_desc'] = 'Cours dont les activités forment la bibliothèque (PDF, vidéos, checklists, modèles).';
$string['settings_behaviour'] = 'Comportement';
$string['settings_programme'] = 'Programme';
$string['settings_programme_desc'] = 'Moodle reste le moteur invisible : ces réglages relient les espaces Alpha Trade aux cours et activités Moodle.';
$string['settings_spaces'] = 'Pratique, ressources et communauté';

// Général.
$string['actions'] = 'Actions';
$string['add'] = 'Ajouter';
$string['all'] = 'Tous';
$string['backtoparcours'] = 'Retour au parcours';
$string['configureprogramme'] = 'Configurer le programme';
$string['continue'] = 'Continuer';
$string['date'] = 'Date';
$string['deleted'] = 'Élément supprimé.';
$string['download'] = 'Télécharger';
$string['feedback'] = 'Commentaire';
$string['filter'] = 'Filtrer';
$string['filters'] = 'Filtres';
$string['hidden'] = 'masqué';
$string['invalidnumber'] = 'Nombre invalide.';
$string['new'] = 'Nouvelle';
$string['noprogramme'] = 'Le programme n\'est pas encore configuré.';
$string['open'] = 'Ouvrir';
$string['progress'] = 'Progression';
$string['resetfilters'] = 'Réinitialiser';
$string['result'] = 'Résultat';
$string['results'] = 'Résultats';
$string['settings'] = 'Paramètres';
$string['start'] = 'Commencer';
$string['state'] = 'État';
$string['status'] = 'Statut';
$string['status_current'] = 'En cours';
$string['status_done'] = 'Terminé';
$string['status_locked'] = 'Verrouillé';
$string['status_todo'] = 'À faire';
$string['use'] = 'Utiliser';

// Tableau de bord.
$string['backtestedtrades'] = 'Trades backtestés';
$string['backtestedtrades_sub'] = 'dans le Backtesting Lab';
$string['badges'] = 'Badges';
$string['badges_sub'] = 'obtenus';
$string['continuelesson'] = 'Continuer la leçon';
$string['dashboard'] = 'Tableau de bord';
$string['greeting'] = 'Bonjour {$a}';
$string['lessonsdonecounter'] = '{$a->done} / {$a->total} leçons terminées';
$string['modulecounter'] = '{$a->done} / {$a->total} modules terminés';
$string['modulepercent'] = 'Module terminé à {$a} %';
$string['openmodule'] = 'Ouvrir le module';
$string['programmedone'] = 'Programme terminé';
$string['programmedone_title'] = 'Toutes les leçons sont validées. Place au projet final.';
$string['seeparcours'] = 'Voir le parcours';
$string['streak'] = 'Série en cours';
$string['streak_sub'] = 'jours consécutifs';
$string['weekcounter'] = 'Semaine {$a->week} / {$a->weeks}';
$string['welcome'] = 'Bienvenue dans votre parcours Alpha Trade.';
$string['yourparcours'] = 'Votre parcours';
$string['yourprogress'] = 'Votre progression';

// Parcours, module, leçon.
$string['editinmoodle'] = 'Modifier dans Moodle';
$string['evaluation'] = 'Évaluation';
$string['lessoncounter'] = 'Leçon {$a->index} / {$a->count}';
$string['lessons'] = 'Leçons';
$string['modulelabel'] = 'Module {$a}';
$string['modulelocked'] = 'Module verrouillé';
$string['modulemeta'] = '{$a->lessons} leçons · {$a->percent} %';
$string['modules'] = 'Modules';
$string['month1theme'] = 'Comprendre';
$string['month2theme'] = 'Analyser';
$string['month3theme'] = 'Mesurer';
$string['monthlabel'] = 'Mois {$a}';
$string['nextlesson'] = 'Leçon suivante';
$string['nextmodule'] = 'Module suivant';
$string['objectives'] = 'Objectifs';
$string['parcours'] = 'Mon parcours';
$string['programmelead'] = 'Comprendre, analyser, tester, mesurer, construire : un module après l\'autre.';
$string['programmetitle'] = 'Programme intensif - 3 mois';
$string['quizzes'] = 'Évaluations';
$string['startevaluation'] = 'Passer l\'évaluation';

// Pratique et analyse de graphique.
$string['analysis_bias'] = 'Biais';
$string['analysis_liquidity'] = 'Liquidité';
$string['analysis_liquidity_ph'] = 'Zones de liquidité, equal highs / lows, sweeps...';
$string['analysis_reviewed'] = 'Corrigée : {$a} / 100';
$string['analysis_scenario'] = 'Scénario';
$string['analysis_scenario_ph'] = 'Conditions, entrée, invalidation, objectif.';
$string['analysis_structure'] = 'Structure';
$string['analysis_structure_ph'] = 'Tendance, HH / HL, BOS, CHoCH...';
$string['analysis_submitted'] = 'Soumise';
$string['analysis_todo'] = 'À faire';
$string['analysis_waiting'] = 'votre analyse attend la correction du formateur. Vous pouvez encore la modifier.';
$string['analysisincomplete'] = 'Renseignez au minimum la structure, le biais et le scénario.';
$string['analysisnumber'] = 'Analyse {$a}';
$string['analysissaved'] = 'Analyse soumise.';
$string['bias_bearish'] = 'Bearish';
$string['bias_bullish'] = 'Bullish';
$string['bias_neutral'] = 'Neutre';
$string['chartnotavailable'] = 'Cet exercice n\'est pas disponible.';
$string['charts_lead'] = 'Analysez une situation réelle, soumettez votre lecture, recevez la correction du formateur.';
$string['charttitle'] = 'Titre de l\'exercice';
$string['confirmdeletechart'] = 'Supprimer l\'exercice "{$a}" et toutes les analyses des étudiants ?';
$string['correct'] = 'Corriger';
$string['correctionsubtitle'] = 'Analyse du formateur';
$string['correctiontitle'] = 'Correction Alpha Trade';
$string['crit_partial'] = 'Partiel';
$string['crit_right'] = 'Juste';
$string['crit_wrong'] = 'À revoir';
$string['critbias'] = 'Biais';
$string['critentry'] = 'Entrée';
$string['critliquidity'] = 'Liquidité';
$string['critstructure'] = 'Structure';
$string['editanalysis'] = 'Modifier mon analyse';
$string['filter_all'] = 'Toutes';
$string['filter_reviewed'] = 'Corrigées';
$string['filter_submitted'] = 'À corriger';
$string['instructions'] = 'Consignes';
$string['invalidsymbol'] = 'Symbole invalide. Format attendu : PLACE:SYMBOLE, par exemple FX:EURUSD.';
$string['newchart'] = 'Nouvel exercice';
$string['nocharts'] = 'Aucun exercice d\'analyse pour le moment.';
$string['nopracticeitems'] = 'Aucune activité disponible pour le moment.';
$string['noreviews'] = 'Aucune analyse à afficher.';
$string['nosignalsrule'] = 'Pas de signaux. Pas de promesses. Argumentez votre analyse.';
$string['practice_cases'] = 'Cas pratiques';
$string['practice_cases_desc'] = 'Prenez une décision et argumentez-la.';
$string['practice_challenges'] = 'Challenges';
$string['practice_challenges_desc'] = 'Testez votre niveau.';
$string['practice_charts'] = 'Graphiques';
$string['practice_charts_desc'] = 'Analysez des situations de marché.';
$string['practice_lead'] = 'Maintenant, vous ne regardez plus seulement le marché. Vous devez l\'analyser.';
$string['practice_title'] = 'Maintenant, c\'est à vous d\'analyser.';
$string['practicelab'] = 'Practice Lab';
$string['referencecorrection'] = 'Correction de référence';
$string['referencecorrection_help'] = 'Analyse du formateur, affichée à l\'étudiant une fois sa copie corrigée.';
$string['reviews'] = 'Travaux à corriger';
$string['reviews_lead'] = 'Analyses de graphique soumises par les étudiants.';
$string['reviewsaved'] = 'Correction enregistrée, l\'étudiant a été notifié.';
$string['savereview'] = 'Enregistrer la correction';
$string['scoreout100'] = 'Note sur 100';
$string['scorerange'] = 'La note doit être comprise entre 0 et 100.';
$string['seechallenges'] = 'Voir les défis';
$string['sortorder'] = 'Ordre';
$string['student'] = 'Étudiant';
$string['studentanalysis'] = 'Analyse de l\'étudiant';
$string['submitanalysis'] = 'Soumettre mon analyse';
$string['submittedon'] = 'Soumise le {$a}';
$string['symbol'] = 'Symbole TradingView';
$string['symbol_help'] = 'Format PLACE:SYMBOLE tel qu\'affiché sur TradingView, par exemple FX:EURUSD, OANDA:XAUUSD, BINANCE:BTCUSDT, CAPITALCOM:US500.';
$string['timeframe'] = 'Unité de temps';
$string['youranalysis'] = 'Votre analyse';

// Backtesting Lab.
$string['addtrade'] = 'Ajouter un trade';
$string['avgloss'] = 'Perte moyenne';
$string['avgwin'] = 'Gain moyen';
$string['backtesting'] = 'Backtesting';
$string['backtesting_lead'] = 'Testez vos setups sur des données historiques. Les statistiques se calculent à chaque trade.';
$string['backtestinglab'] = 'Backtesting Lab';
$string['candles'] = 'Bougies';
$string['chartview'] = 'Vue du graphique';
$string['confirmdeletestrategy'] = 'Supprimer la stratégie "{$a}" et tous ses trades ?';
$string['curve'] = 'Courbe';
$string['direction'] = 'Direction';
$string['direction_long'] = 'Achat';
$string['direction_short'] = 'Vente';
$string['editstrategy'] = 'Modifier la stratégie';
$string['entry'] = 'Entrée';
$string['equitycurve'] = 'Equity curve';
$string['expectancy'] = 'Expectancy';
$string['invalidresultr'] = 'Indiquez le résultat en R, par exemple 2 ou -1.';
$string['market'] = 'Marché';
$string['maxconsecutivelosses'] = 'Pertes consécutives max';
$string['maxdrawdown'] = 'Max drawdown';
$string['minsample'] = 'Aucun avantage statistique ne peut être revendiqué sous {$a} trades.';
$string['mystrategies'] = 'Mes stratégies';
$string['newstrategy'] = 'Nouvelle stratégie';
$string['newstrategy_lead'] = 'Définissez le marché, l\'unité de temps et les règles avant de saisir le premier trade.';
$string['nostrategies'] = 'Aucune stratégie pour le moment. Commencez par définir vos règles.';
$string['notes'] = 'Notes';
$string['notrades'] = 'Aucun trade saisi pour le moment.';
$string['period'] = 'Période testée';
$string['period_ph'] = 'Ex. janvier 2023 à juin 2025';
$string['profitfactor'] = 'Profit factor';
$string['resultr'] = 'Résultat (R)';
$string['resultr_help'] = 'Résultat exprimé en multiple du risque : +2 pour un gain de deux fois le risque, -1 pour un stop touché, 0 pour un breakeven.';
$string['savestrategy'] = 'Enregistrer la stratégie';
$string['savetrade'] = 'Enregistrer';
$string['seereport'] = 'Voir mon rapport';
$string['seetrades'] = 'Voir les trades';
$string['statistics'] = 'Statistiques';
$string['stoploss'] = 'Stop loss';
$string['stoploss_short'] = 'SL';
$string['strategy'] = 'Stratégie';
$string['strategycreated'] = 'Stratégie créée. Ajoutez vos trades historiques.';
$string['strategyname'] = 'Nom de la stratégie';
$string['strategyname_ph'] = 'Ex. Alpha Setup #01';
$string['strategyrules'] = 'Règles de la stratégie';
$string['takeprofit'] = 'Take profit';
$string['takeprofit_short'] = 'TP';
$string['totalr'] = 'Résultat total';
$string['tradedate'] = 'Date du trade';
$string['trades'] = 'Trades';
$string['tradesaved'] = 'Trade enregistré.';
$string['tradeslist'] = 'Trades';
$string['verdict'] = 'Verdict';
$string['verdict_negative'] = 'L\'expectancy est négative sur l\'échantillon testé : la stratégie ne montre pas d\'avantage statistique en l\'état.';
$string['verdict_positive'] = 'Votre stratégie présente une expectancy positive sur l\'échantillon testé.';
$string['verdict_small'] = 'L\'échantillon actuel ne permet pas de démontrer un avantage statistique suffisant.';
$string['winrate'] = 'Win rate';

// Journal de trading.
$string['asset'] = 'Actif';
$string['assetclass'] = 'Classe d\'actif';
$string['assetclass_crypto'] = 'Crypto';
$string['assetclass_forex'] = 'Forex';
$string['assetclass_gold'] = 'Or';
$string['assetclass_indices'] = 'Indices';
$string['assetclass_other'] = 'Autre';
$string['assetclass_stocks'] = 'Actions';
$string['confirmdeletetrade'] = 'Supprimer ce trade du journal ?';
$string['edittrade'] = 'Modifier le trade';
$string['emotion'] = 'État émotionnel';
$string['emotion_all'] = 'Toutes les émotions';
$string['emotion_calm'] = 'Calme';
$string['emotion_confident'] = 'Confiant';
$string['emotion_euphoric'] = 'Euphorique';
$string['emotion_frustrated'] = 'Frustré';
$string['emotion_hesitant'] = 'Hésitant';
$string['emotion_stressed'] = 'Stressé';
$string['journal'] = 'Journal de trading';
$string['journal_after'] = 'Après le trade';
$string['journal_before'] = 'Avant le trade';
$string['journal_context'] = 'Quel était le contexte ?';
$string['journal_followedplan'] = 'Ai-je respecté mon plan ?';
$string['journal_form_lead'] = 'Un trade, avant et après : c\'est ici que la psychologie rejoint les chiffres.';
$string['journal_lead'] = 'Chaque trade documenté : pourquoi, comment, et ce que vous en retenez.';
$string['journal_reason'] = 'Pourquoi suis-je entré ?';
$string['journal_review'] = 'Bilan';
$string['journal_title'] = 'Journal de trading';
$string['journal_trade'] = 'Le trade';
$string['journalentries'] = 'Trades journalisés';
$string['nojournal'] = 'Aucun trade ne correspond. Ajoutez votre premier trade au journal.';
$string['period_all'] = 'Toutes les dates';
$string['period_month'] = 'Ce mois-ci';
$string['period_week'] = 'Cette semaine';
$string['planfollowed'] = 'Plan respecté';
$string['plannotfollowed'] = 'Plan non respecté';
$string['result_all'] = 'Tous les résultats';
$string['result_loss'] = 'Pertes';
$string['result_win'] = 'Gains';
$string['riskpct'] = 'Risque (%)';
$string['screenshot'] = 'Capture d\'écran';
$string['setup'] = 'Setup';
$string['setup_ph'] = 'Ex. Sweep + BOS';
$string['weeksummary'] = 'Cette semaine - {$a->trades} trades · {$a->r} · {$a->winrate} % de réussite';

// Outils.
$string['breakevenwinrate'] = 'Win rate de rentabilité';
$string['calculate'] = 'Calculer';
$string['calculator_empty'] = 'Renseignez les champs pour obtenir le résultat.';
$string['invalidstop'] = 'Le stop doit être différent de l\'entrée et placé du bon côté du trade.';
$string['positionsize'] = 'Taille de position';
$string['riskamount'] = 'Montant risqué (1R)';
$string['riskwarning'] = 'Plus de 2 % de risque par trade : vérifiez que c\'est cohérent avec votre plan.';
$string['rrratio'] = 'Ratio risque / rendement';
$string['stopdistance'] = 'Distance du stop';
$string['tool_field_capital'] = 'Capital';
$string['tool_field_entry'] = 'Prix d\'entrée';
$string['tool_field_pointvalue'] = 'Valeur d\'un point pour 1 unité';
$string['tool_field_pointvalue_help'] = 'Gain ou perte pour un mouvement de 1 point du prix avec une unité (1 pour des actions, valeur du pip x 10 000 pour un lot forex...).';
$string['tool_field_riskpct'] = 'Risque par trade (%)';
$string['tool_field_stoploss'] = 'Stop loss';
$string['tool_field_takeprofit'] = 'Objectif (take profit)';
$string['tool_position'] = 'Position size';
$string['tool_position_desc'] = 'Taille de position à partir du risque et de la distance du stop.';
$string['tool_risk'] = 'Calculateur de risque';
$string['tool_risk_desc'] = 'Montant risqué par trade selon votre capital.';
$string['tool_rr'] = 'Risk / Reward';
$string['tool_rr_desc'] = 'Ratio risque / rendement et win rate minimum pour être rentable.';
$string['tool_stats'] = 'Statistiques';
$string['tool_stats_desc'] = 'Statistiques de vos stratégies dans le Backtesting Lab.';
$string['tools'] = 'Outils';
$string['tools_lead'] = 'Les calculs de base avant chaque trade.';
$string['tools_title'] = 'Outils Alpha Trade';

// Ressources.
$string['noresources'] = 'Aucune ressource ne correspond.';
$string['resources'] = 'Ressources';
$string['resources_lead'] = 'Fiches, checklists, modèles et guides du programme.';
$string['resources_title'] = 'Bibliothèque Alpha Trade';
$string['resourcesnotconfigured'] = 'La bibliothèque n\'est pas encore configurée.';
$string['restype_checklist'] = 'Checklist';
$string['restype_pdf'] = 'PDF';
$string['restype_template'] = 'Modèle';
$string['restype_video'] = 'Vidéo';
$string['restypes_checklist'] = 'Checklists';
$string['restypes_pdf'] = 'PDF';
$string['restypes_template'] = 'Modèles';
$string['restypes_video'] = 'Vidéos';
$string['searchresources'] = 'Rechercher une ressource';

// Communauté.
$string['community'] = 'Communauté';
$string['community_analyses'] = 'Analyses de marché';
$string['community_analyses_desc'] = 'Partagez des analyses argumentées et discutez-les.';
$string['community_announcements'] = 'Annonces Alpha Trade';
$string['community_announcements_desc'] = 'Informations officielles des formateurs.';
$string['community_lead'] = 'Trois espaces, pas plus. Échanger, questionner, argumenter.';
$string['community_qa'] = 'Questions et réponses';
$string['community_qa_desc'] = 'Posez vos questions sur le programme et les notions.';
$string['community_title'] = 'La communauté Alpha Trade';
$string['discussioncount'] = '{$a} discussions';
$string['nocommunity'] = 'Les espaces de la communauté ne sont pas encore configurés.';
$string['nosignals'] = 'Alpha Trade ne fournit pas de signaux de trading.';

// Profil, projet final, certification.
$string['alphatradingsystem'] = 'Alpha Trading System';
$string['certification'] = 'Certification';
$string['certification_eyebrow'] = 'Alpha Trade - Certification de fin de programme';
$string['certification_lead'] = 'Les conditions pour obtenir votre certificat.';
$string['certification_locked'] = 'Le certificat se débloque quand toutes les conditions sont remplies.';
$string['certification_mention'] = 'a complété le programme intensif : Investissement · Analyse ICT/SMC · Trading quantitatif.';
$string['cond_backtest'] = 'Backtesting effectué';
$string['cond_challenges'] = 'Challenges réalisés';
$string['cond_lessons'] = 'Cours complétés';
$string['cond_project'] = 'Projet final validé';
$string['cond_quizzes'] = 'Quiz validés';
$string['conditions'] = 'Conditions';
$string['continueproject'] = 'Continuer mon projet';
$string['downloadcertificate'] = 'Télécharger mon certificat';
$string['finalproject'] = 'Projet final';
$string['finalproject_lead'] = 'Construisez, testez et documentez votre propre méthode.';
$string['nobadges'] = 'Aucun badge n\'est encore défini pour le programme.';
$string['noresults'] = 'Aucune stratégie backtestée pour le moment.';
$string['nosteps'] = 'Les étapes du projet ne sont pas encore publiées.';
$string['profile'] = 'Profil et progression';
$string['projectprogress'] = 'du projet réalisé';
$string['steps'] = 'Étapes';
$string['tradesof'] = '{$a->done} / {$a->min} trades';
$string['training'] = 'Formation';
$string['yourresults'] = 'Vos résultats';

// Espace formateur.
$string['alert_inactive'] = 'Aucune activité depuis {$a} jours';
$string['alert_neveraccessed'] = 'N\'a jamais ouvert le programme';
$string['alert_notstarted'] = '"{$a->done}" terminé, "{$a->next}" pas encore commencé';
$string['alert_quizfailures'] = '{$a->count} échecs à "{$a->quiz}"';
$string['currentmodule'] = 'Module en cours';
$string['kpi_active'] = 'Actifs cette semaine';
$string['kpi_blocked'] = 'Étudiants bloqués';
$string['kpi_enrolled'] = 'Inscrits';
$string['kpi_late'] = 'En retard';
$string['kpi_tograde'] = 'Travaux à corriger';
$string['noalerts'] = 'Aucune alerte pédagogique : tous les étudiants avancent.';
$string['nostrategiesstudents'] = 'Aucun étudiant n\'a encore créé de stratégie.';
$string['nostudents'] = 'Aucun étudiant inscrit au programme.';
$string['pedagogicalalerts'] = 'Alertes pédagogiques';
$string['sampleok'] = 'Échantillon suffisant';
$string['samplesmall'] = 'Échantillon insuffisant';
$string['student_blocked'] = 'Bloqué';
$string['student_late'] = 'En retard';
$string['student_ok'] = 'À jour';
$string['students'] = 'Étudiants';
$string['teacherdashboard'] = 'Tableau formateur';
$string['teacherspace'] = 'Espace formateur';
$string['tradesminimum'] = 'Minimum attendu pour la certification : {$a} trades par stratégie.';
