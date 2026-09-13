# Alpha Trade - skin Moodle

Habillage complet de Moodle pour Alpha Trade : l'étudiant utilise Alpha Trade, Moodle reste le moteur invisible (comptes, cours, quiz, achèvement, badges, certificats).

Deux plugins, à installer ensemble :

| Plugin | Rôle |
|---|---|
| `theme/alphatrade` | Thème enfant de Boost : palette sombre `#181a1e` + or mat, Inter et Phosphor auto-hébergés, shell d'application (header, sidebar, tab bar mobile), connexion, site public, lecteur de leçon, quiz, restyle des pages Moodle natives. |
| `local/alphatrade` | Portail étudiant : tableau de bord, parcours, modules, Practice Lab (analyse de graphique TradingView + correction), Backtesting Lab, journal de trading, outils, ressources, communauté, profil, projet final, certification, espace formateur. |

Compatibilité : **Moodle 4.5 LTS** (Bootstrap 4), PHP 8.1+. Le serveur Oracle tourne en 4.5.13+.

## Installation sur le serveur

```bash
ssh -i <clé> opc@<ip>
cd /tmp && git clone https://github.com/upscaylman/moodle_skin.git
sudo cp -r /tmp/moodle_skin/theme/alphatrade /var/www/moodle/theme/
sudo cp -r /tmp/moodle_skin/local/alphatrade /var/www/moodle/local/
sudo chown -R apache:apache /var/www/moodle/theme/alphatrade /var/www/moodle/local/alphatrade
sudo -u apache php /var/www/moodle/admin/cli/upgrade.php --non-interactive
sudo -u apache php /var/www/moodle/admin/cli/purge_caches.php
```

Puis dans Moodle :

1. **Administration > Apparence > Thèmes** : sélectionner Alpha Trade.
2. **Administration > Plugins > Plugins locaux > Alpha Trade** : choisir le cours du programme, le cours Pratique, le cours Ressources, les forums.
3. **Administration > Apparence > Navigation > Page d'accueil par défaut** : "Tableau de bord" (Alpha Trade).
4. **Administration > Apparence > Alpha Trade** : lien "Candidater", e-mail de contact, site public pour les visiteurs.

Pour tester sans changer le thème de tous les utilisateurs : activer `allowthemechangeonurl` puis ouvrir `/?theme=alphatrade`.

## Organiser le contenu dans Moodle

Le plugin lit la structure Moodle, il n'y a rien à dupliquer.

**Cours du programme** (un seul cours, format Thématique)
- Chaque **section** = un module (01 à 12). Le nom de la section = le nom du module.
- Résumé de section : les paragraphes = description, la **liste à puces** = objectifs (checklist).
- Chaque **activité** = une leçon (Page, URL vidéo, Fichier, H5P...). Le **Test** de la section = l'évaluation du module.
- **Restrictions d'accès** = modules et leçons verrouillés. **Suivi d'achèvement** activé = progression.
- Réglage "Modules par mois" (ex. `3,4,5`) pour le regroupement Comprendre / Analyser / Mesurer.
- La dernière section (ou celle du réglage) = étapes du projet final "Alpha Trading System".
- Dans une leçon, un `blockquote` (ou un bloc `.alpha-keypoints`) s'affiche comme "À retenir", une liste `.alpha-resources` comme ligne de ressources.
- Les **badges du cours** apparaissent dans le profil. Le certificat : activité (ex. mod_customcert) dont l'identifiant est mis dans les réglages.

**Cours Pratique** : section 1 = cas pratiques, section 2 = challenges (numéros réglables).
**Cours Ressources** : activités Fichier / URL / Page. Filtres automatiques : PDF, vidéos (YouTube, Vimeo, H5P), "checklist" et "modèle" dans le nom de l'activité ou de la section.
**Communauté** : forum Annonces du cours programme + deux forums choisis dans les réglages (Q&R, Analyses de marché).

Les exercices d'**analyse de graphique** se créent dans Pratique > Graphiques > Nouvel exercice (symbole TradingView au format `FX:EURUSD`). Les formateurs corrigent dans "Travaux à corriger", l'étudiant reçoit une notification.

## Correspondance écrans

| Écran (wireframes v3) | Fichier |
|---|---|
| Shell (sidebar, header, tab bar 5 items, menu mobile) | `theme/alphatrade/layout/app.php`, `templates/app, header, sidebar, navlist, tabbar, mobilemenu` |
| Connexion | `theme/alphatrade/layout/login.php`, `templates/login`, `templates/core/loginform` |
| Site public | `theme/alphatrade/layout/frontpage.php`, `templates/public`, `classes/output/public_site.php` |
| 1 Dashboard | `local/alphatrade/index.php` |
| 2 Mon parcours | `parcours.php` |
| 3 Module | `module.php` |
| 4 Lecteur de leçon, 5 Quiz | pages Moodle natives + `theme/alphatrade/templates/lesson_header, lesson_footer`, `scss/post/_quiz.scss` |
| 6 Practice Lab | `practice.php` |
| 7 Analyse de graphique | `charts.php`, `chart.php`, `chartedit.php`, `reviews.php`, `review.php` |
| 8-9 Backtesting Lab, dashboard stratégie | `backtesting.php`, `strategy.php` (stats en R, equity curve SVG, vue bougies TradingView) |
| 10 Journal de trading, fiche trade | `journal.php`, `journalentry.php` |
| 11 Outils | `tools.php` (risque, position size, risk/reward) |
| 12 Ressources | `resources.php` |
| 13 Communauté | `community.php` |
| 14 Projet final, certification, profil | `project.php`, `certificate.php`, `profile.php` |
| Espace formateur (KPI, alertes pédagogiques, backtests des élèves) | `teacher.php` |

## Système de design

Tokens dans `theme/alphatrade/scss/pre.scss` (fond, surfaces, or mat et son échelle 100-900, couleurs fonctionnelles, rayons, ombres). La gamme de gris Bootstrap est inversée pour que toutes les pages Moodle natives passent en sombre. Couleur d'accent réglable dans l'administration.

Règles appliquées partout : icônes Phosphor uniquement, aucun emoji, jamais de tiret cadratin, couleur fonctionnelle toujours doublée d'une icône ou d'un texte, dégradé décoratif uniquement derrière le logo / la navbar.

## Outils de développement

```bash
cd tools && npm install
node build-icons.mjs     # régénère scss/post/_icons.scss avec les seules icônes Phosphor utilisées
node check-strings.mjs   # vérifie les chaînes EN/FR, l'absence d'emoji et de tiret cadratin
```

Après toute modification SCSS sur le serveur : purger les caches.

## Sécurité

- Aucune clé d'API côté client : TradingView est intégré en widget public sans clé.
- Formulaires protégés par `sesskey`, paramètres typés, données d'un étudiant visibles uniquement par lui et par les formateurs (capacité `local/alphatrade:viewstudentdata`).
- Captures du journal servies par `pluginfile` avec contrôle d'accès. Données personnelles couvertes par l'API vie privée (export et suppression).

## Reste à faire

- Harmoniser au pixel près avec les maquettes `.dc.html` de Claude Design (non incluses dans le zip de handoff : seul `DESIGN_SYSTEM.md` a servi de référence).
- Proxy serveur vers l'API de données de marché (backtests automatiques sur données réelles).
- Messagerie et paiement, restyle détaillé des pages d'administration.
