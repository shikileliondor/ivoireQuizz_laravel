# IvoireQuiz V1 officielle — Documentation

IvoireQuiz est une API Laravel destinée à alimenter une application mobile Flutter de quiz gamifié autour des régions, villes et communes de Côte d’Ivoire.

## Objectif de la V1 officielle

La V1 officielle fournit un socle backend stable pour :

- authentifier les joueurs ;
- exposer le parcours de jeu région → ville/commune → niveau ;
- démarrer, suivre et terminer des sessions de quiz ;
- calculer côté serveur les scores, XP, coins, gems et récompenses ;
- gérer les vies, streaks, ligues hebdomadaires, coffres, collections et passeports ;
- offrir à Flutter une API JSON cohérente sous `/api/v1`.

## Stack utilisée

- **Backend** : Laravel API.
- **Authentification** : Laravel Sanctum avec Bearer token.
- **Base de données** : MySQL, source de vérité.
- **Cache / queues / classements** : Redis.
- **Mobile** : Flutter, consommateur de l’API V1.
- **Tests** : Feature tests Laravel, avec extension progressive vers des tests de services.

## Fonctionnalités principales

- **Parcours régions/villes/niveaux** : progression géographique et pédagogique.
- **Vies** : limitation de lancement de session et régénération serveur.
- **Streak** : récompense la régularité quotidienne.
- **Ligues** : classement hebdomadaire basé sur l’XP gagnée.
- **Coffres** : récompenses aléatoires ouvrables une seule fois.
- **Collections** : personnalités et monuments déblocables.
- **Passeport** : tampons régionaux gagnés après boss final.
- **API mobile Flutter** : endpoints JSON normalisés et sécurisés.

## Fichiers de documentation

| Fichier | Rôle |
| --- | --- |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Architecture globale, dossiers et principes backend. |
| [`DATABASE.md`](DATABASE.md) | Tables, relations, règles et décisions de modélisation. |
| [`GAMEPLAY.md`](GAMEPLAY.md) | Boucle de jeu complète et concepts gameplay. |
| [`SERVICES.md`](SERVICES.md) | Services métier, responsabilités, entrées/sorties et erreurs. |
| [`SECURITY.md`](SECURITY.md) | Auth, ownership, rate limiting, anti-triche et logs suspects. |
| [`REDIS_AND_QUEUES.md`](REDIS_AND_QUEUES.md) | Usage Redis, cache, queues et classements. |
| [`API_V1.md`](API_V1.md) | Référence détaillée des endpoints `/api/v1`. |
| [`FLUTTER_INTEGRATION.md`](FLUTTER_INTEGRATION.md) | Guide d’intégration Flutter avec exemples Dio. |
| [`DEPLOYMENT.md`](DEPLOYMENT.md) | Déploiement VPS, commandes Laravel, Supervisor et cron. |
| [`TESTING.md`](TESTING.md) | Tests existants, commandes et cas critiques. |

## Principe important

Flutter affiche et transmet les actions utilisateur, mais **le backend calcule tout** : score, XP, coins, gems, réussite, progression, coffres, collections et passeport.
