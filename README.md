# Alpha Trade - skin Moodle

Habillage complet de Moodle pour Alpha Trade : l'étudiant utilise Alpha Trade, Moodle reste le moteur invisible (comptes, cours, quiz, achèvement, badges, certificats, paiements, messagerie).

Les écrans sont alignés sur les maquettes Claude Design du dossier `Redesign Moodle complet 2` (Portail Etudiant, Espace Enseignant, Espace Admin, Messagerie, Paiement, Connexion, Site Public) et le système de design Nocturne.

Deux plugins, à installer ensemble :

| Plugin | Rôle |
|---|---|
| `theme/alphatrade` | Thème enfant de Boost : tokens Nocturne, Inter et Phosphor auto-hébergés, shell d'application (sidebar étudiant / enseignant / admin, en-tête, barre mobile), connexion, site public, lecteur de leçon, quiz, restyle des pages Moodle natives. |
| `local/alphatrade` | Portail étudiant, espace enseignant, espace admin, messagerie, facturation, candidatures, proxy des données de marché. |

Compatibilité : **Moodle 4.5 LTS** (Bootstrap 4), PHP 8.1+. Le serveur Oracle tourne en 4.5.13+.

## Installation sur le serveur

```bash
ssh -i <clé> opc@<ip>
cd /tmp && git clone https://github.com/upscaylman/moodle_skin.git
sudo cp -r /tmp/moodle_skin/theme/alphatrade /var/www/moodle/theme/
sudo cp -r /tmp/moodle_skin/local/alphatrade /var/www/moodle/local/
# Le code Moodle appartient à root et reste en lecture seule pour Apache.
sudo chown -R root:root /var/www/moodle/theme/alphatrade /var/www/moodle/local/alphatrade
sudo -u apache php /var/www/moodle/admin/cli/upgrade.php --non-interactive
sudo -u apache php /var/www/moodle/admin/cli/purge_caches.php
```

Puis dans Moodle :

1. **Administration > Apparence > Thèmes** : sélectionner Alpha Trade.
2. **Administration > Plugins > Plugins locaux > Alpha Trade** : cours du programme, cours Pratique, cours Ressources, forums, marchés, clé de données de marché.
3. **Administration > Apparence > Navigation > Page d'accueil par défaut** : "Tableau de bord" (Alpha Trade).
4. **Administration > Apparence > Alpha Trade** : site public pour les visiteurs, e-mail de contact.

Pour tester sans changer le thème de tous les utilisateurs : activer `allowthemechangeonurl` puis ouvrir `/?theme=alphatrade`.

## Organiser le contenu dans Moodle

Le plugin lit la structure Moodle, il n'y a rien à dupliquer.

**Cours du programme** (un seul cours, format Thématique)
- Chaque **section** = un module. Le nom de la section = le nom du module.
- Résumé de section : les paragraphes = description, la **liste à puces** = objectifs.
- Chaque **activité** = une leçon (Page, URL vidéo, Fichier, H5P...). Le **Test** de la section = l'évaluation du module.
- **Restrictions d'accès** = modules et leçons verrouillés. **Suivi d'achèvement** activé = progression.
- Réglage "Modules par mois" (ex. `3,4,5`) pour le regroupement Comprendre / Analyser / Mesurer.
- La dernière section (ou celle du réglage) = étapes du projet final "Alpha Trading System".
- Les **badges du cours** apparaissent dans le profil. Le certificat : activité (ex. mod_customcert) dont l'identifiant est mis dans les réglages.

**Cours Pratique** : section 1 = cas pratiques, section 2 = challenges. **Cours Ressources** : activités Fichier / URL / Page. **Communauté** : forum Annonces du programme + forums Q&R et Analyses choisis dans les réglages.

Les exercices d'**analyse de graphique** se créent dans Pratique > Graphiques. Les formateurs corrigent dans "Devoirs à corriger" (analyses + devoirs Moodle du programme et du cours Pratique).

## Correspondance écrans

| Maquette | Fichier |
|---|---|
| Shell étudiant / enseignant / admin | `theme/alphatrade/layout/app.php`, `classes/output/navigation.php`, `templates/app, header, sidebar, mobtop, tabbar, mobilemenu` |
| Connexion | `theme/alphatrade/layout/login.php`, `templates/login`, `templates/core/loginform` |
| Site Public (accueil, programme, méthode, formateurs, FAQ, candidater) | `theme/alphatrade/layout/frontpage.php` (`/?view=...`), `templates/public`, `local/alphatrade/apply.php` |
| Portail : accueil, parcours, module | `index.php`, `parcours.php`, `module.php` |
| Portail : leçon, évaluation | pages Moodle natives + `templates/lesson_header, lesson_footer`, `scss/post/_quiz.scss` |
| Portail : pratique, analyse | `practice.php`, `charts.php`, `chart.php` |
| Portail : backtest, stratégie | `backtesting.php`, `strategy.php` |
| Portail : journal, fiche de trade | `journal.php`, `journalentry.php` |
| Portail : outils, ressources, communauté | `tools.php`, `resources.php`, `community.php` |
| Portail : profil, certification, projet final | `profile.php`, `certificate.php`, `project.php` |
| Enseignant : tableau de bord, devoirs, backtests des élèves | `teacher.php`, `reviews.php`, `review.php`, `teacher.php?view=backtests` |
| Admin : tableau de bord, utilisateurs, cours, cohortes, rapports, candidatures | `admin.php?view=...` |
| Messagerie (messages, notifications) | `messages.php` |
| Paiement & facturation, reçu | `billing.php`, `receipt.php` |

## Données de marché (proxy serveur)

Les bougies réelles (vue "Bougies (marché réel)" d'une stratégie, vérification des trades backtestés) viennent de l'API London Strategic Edge, appelée **uniquement par le serveur Moodle** (`classes/local/marketdata.php`, service web `local_alphatrade_get_candles`).

- Clé : réglage "Clé d'API" (champ masqué) ou variable d'environnement `ALPHATRADE_LSE_API_KEY` du serveur. Jamais envoyée au navigateur.
- Cache MUC (durée réglable) et limite de requêtes par utilisateur par heure.
- Sans clé, la vue bougies retombe sur le widget TradingView public (sans clé).
- Marchés : réglage "Marchés", une ligne par marché `libellé|symbole TradingView|symbole London Strategic Edge`.

## Messagerie

`messages.php` reprend la maquette Messagerie sur l'API `core_message` : permissions, confidentialité et blocages Moodle s'appliquent. Onglet Notifications = notifications popup Moodle, "Tout marquer comme lu". Avec le réglage "Messagerie Alpha Trade", les pages `/message/index.php` et `/message/output/popup/notifications.php` redirigent vers cette page.

## Paiement

`billing.php` reprend la maquette Paiement sur `core_payment` et l'inscription payante (`enrol_fee`) :

1. Activer un moyen de paiement (**Administration > Plugins > Moyens de paiement**, ex. PayPal ou Stripe) et créer un compte de paiement.
2. Activer "Inscription payante" et ajouter la méthode aux cours vendus (programme, options).
3. Plan actuel, historique et reçus viennent de la table des paiements ; "Ajouter un module" ouvre la fenêtre de paiement Moodle. Aucune donnée bancaire ne transite par Alpha Trade.

## Candidatures

Le formulaire "Candidater" du site public enregistre la candidature (`local_alphatrade_application`), notifie les administrateurs (préférence de notification "Nouvelle candidature") et limite à 3 envois par heure par adresse IP (+ champ piège anti-robots). Suivi dans Admin > Tableau de bord > Candidatures.

## Système de design

Tokens Nocturne dans `theme/alphatrade/scss/pre.scss`, composants dans `scss/post/_nocturne.scss`, écrans dans `_screens`, `_staff`, `_messaging`, `_billing`, `_login`, `_public`. Couleur d'accent réglable dans l'administration.

Règles appliquées partout : icônes Phosphor uniquement, aucun emoji, jamais de tiret cadratin, couleur fonctionnelle toujours doublée d'une icône ou d'un texte.

## Outils de développement

```bash
cd tools && npm install
node build-icons.mjs     # régénère scss/post/_icons.scss avec les seules icônes Phosphor utilisées
node check-strings.mjs   # vérifie les chaînes EN/FR, l'absence d'emoji et de tiret cadratin
```

Après toute modification SCSS sur le serveur : purger les caches.

## Sécurité

- Aucune clé d'API côté navigateur : proxy serveur pour les données de marché, TradingView en widget public.
- Formulaires protégés par `sesskey`, paramètres typés, capacités vérifiées (données étudiantes visibles par l'étudiant et les formateurs uniquement, espace admin réservé à `moodle/site:config`).
- Formulaire public : `sesskey`, champ piège, limitation par IP, validation de l'e-mail.
- Captures du journal servies par `pluginfile` avec contrôle d'accès. Données personnelles couvertes par l'API vie privée.

### Serveur (HTTPS et durcissement)

- HTTPS : certificat Let's Encrypt pour l'adresse IP (profil `shortlived`, renouvellement automatique), redirection HTTP vers HTTPS, `wwwroot` en `https://`, `cookiesecure` activé. Prérequis : ports TCP 80 et 443 ouverts dans la Security List OCI.
- MariaDB écoute uniquement en local, `expose_php` désactivé, en-têtes de sécurité Apache, fichiers sensibles de Moodle bloqués, `directorypermissions` restreint.
