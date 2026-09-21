# Bot Discord du parrainage Alpha Trade

Interface Discord du moteur de parrainage. Le bot ne stocke rien et n'a pas de base de données :
chaque commande interroge Moodle en temps réel (ADR-2). Une panne du bot n'empêche aucun
traitement Moodle, et l'inverse est vrai aussi.

## Ce qu'il fait

| Commande | Réponse (toujours en privé) |
|---|---|
| `/parrainage` | Lien, clics, filleuls, conversions, gains |
| `/lien` | Code et lien de partage |
| `/statistiques` | Les cinq chiffres |
| `/filleuls` | Liste des filleuls, prénom et initiale seulement |
| `/aide-parrainage` | Comment fonctionne le programme |

Toutes les réponses sont éphémères : les chiffres d'un membre n'apparaissent jamais dans un salon.

## Prérequis côté Moodle

1. **Activer les services web** : Administration du site > Général > Avancé > `enablewebservices`.
2. **Activer le protocole REST** : Serveur > Services web > Gérer les protocoles.
3. **Activer le service** « Alpha Trade referral bot » : Serveur > Services web > Services externes.
   Il est livré désactivé et restreint aux utilisateurs autorisés.
4. **Créer un utilisateur de service** (compte dédié, sans privilège d'administration), lui donner
   la capacité `local/alphatrade_referral:viewown`, l'autoriser sur le service, puis générer son
   **jeton** : Serveur > Services web > Gérer les jetons.

Ce jeton est le seul secret Moodle que le bot détient. Il ne donne accès qu'aux quatre fonctions
de lecture du parrainage.

## Prérequis côté Discord

1. Créer une application sur <https://discord.com/developers/applications>. L'onglet « General
   Information » donne l'**identifiant d'application** (`DISCORD_CLIENT_ID`, à reporter aussi dans
   les réglages Moodle du plugin) et la **clé publique** (`DISCORD_PUBLIC_KEY`, inutile au bot
   actuel : elle ne sert qu'à vérifier la signature d'un point d'entrée HTTP « Interactions »).
2. Onglet OAuth2 : ajouter l'URL de retour affichée dans les réglages Moodle du plugin
   (`/local/alphatrade_referral/discord.php?action=callback`), portée `identify`. Le **secret
   client** de cet onglet va dans les réglages Moodle, pas dans le `.env` du bot.
3. Onglet Bot : créer le bot, copier son jeton. **Aucune permission `Administrator`.**
   Portées d'invitation : `bot` + `applications.commands`.
4. Si les rôles communautaires sont utilisés, placer le rôle du bot **au-dessus** des rôles
   qu'il attribue (Parrain, Ambassadeur, Super Parrain).

## Démarrage

```bash
cp .env.example .env     # puis renseigner les variables
npm install
npm run register         # une fois, et à chaque changement de commande
npm start
```

Les deux scripts lisent `.env` avec le `--env-file` natif de Node (d'où `node >= 20.6`) : aucune
dépendance de chargement à installer. Le fichier doit donc exister, même vide, pour lancer le bot
en local.

En production, lancer `npm start` sous un superviseur (systemd, pm2) qui redémarre le processus.

## Secrets

Rien dans le code, rien dans Git : tout passe par `.env`, ignoré par `.gitignore`. Côté Moodle,
les mêmes secrets peuvent être fournis par les variables d'environnement `DISCORD_CLIENT_ID`,
`DISCORD_CLIENT_SECRET`, `DISCORD_BOT_TOKEN`, `DISCORD_GUILD_ID` et `REFERRAL_API_SECRET`, qui
l'emportent toujours sur la valeur enregistrée en base.

## Ce que le bot ne fait pas

- Il ne décide d'aucune récompense : Moodle est la seule autorité.
- Il n'expose jamais un e-mail, une donnée de paiement ni un signal anti-fraude.
- Il n'écrit rien dans Moodle : les quatre fonctions utilisées sont en lecture seule.
