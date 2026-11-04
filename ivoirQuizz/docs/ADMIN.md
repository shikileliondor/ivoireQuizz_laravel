# Back-office administrateur

Interface web de gestion d'IvoireQuizz, accessible sur `/admin` (Blade + Tailwind, sans dépendance supplémentaire).

## Accès

- Seuls les comptes `users.is_admin = true` **et** `status = active` peuvent se connecter.
- Connexion par session web (`/admin/login`), limitée à 5 essais par minute pour un même couple email + IP.
- Un administrateur suspendu, désactivé ou dont les droits sont retirés perd l'accès à sa requête suivante.
- L'API mobile (Sanctum) n'est pas concernée : un admin reste un joueur normal dans l'application.

## Créer le premier administrateur

```bash
php artisan migrate --force
php artisan admin:create admin@exemple.ci --name="Prénom Nom"
```

La commande demande un mot de passe (12 caractères minimum) si le compte n'existe pas encore. Sur un compte existant, elle le nomme administrateur et le réactive ; `--password=` permet aussi de réinitialiser son mot de passe. Les administrateurs suivants peuvent être nommés depuis la fiche joueur.

## Production (LWS)

FTP ne lance pas Artisan. Après l'extraction de l'archive du déploiement qui introduit le back-office, lancer depuis le terminal LWS (ou via le support) :

1. `php artisan migrate --force` : ajoute la colonne `users.is_admin`.
2. `php artisan admin:create <email>` : crée le premier compte.
3. `php artisan storage:link` si ce n'est pas déjà fait : nécessaire pour afficher les images envoyées.

Le déploiement des notifications automatiques ajoute 2 migrations (`php artisan migrate --force`) et une tâche Cron. Voir « Planificateur et file d'attente » plus bas.

## Fonctionnalités

| Section | Ce qu'on peut faire |
| --- | --- |
| Tableau de bord | Indicateurs clés, parties et inscriptions des 14 derniers jours, signalements en attente, meilleurs joueurs |
| Joueurs | Recherche, fiche détaillée, modification du profil, suspension, déconnexion des appareils, ajustement pièces/gemmes, droits admin, suppression anonymisée |
| Parties | Historique filtrable, détail réponse par réponse |
| Régions, villes, niveaux | CRUD de la carte de campagne, images, ordre, récompenses ; le cache `game:regions:map` est vidé à chaque modification |
| Catégories | CRUD |
| Questions | Recherche et filtres, création/édition avec 2 à 6 réponses, activation, archivage |
| Signalements | Traitement (examiné, corrigé, rejeté), application groupée aux signalements de la même question |
| Coffres, collection, ligues | CRUD des récompenses et paliers |
| Notifications | Envoi push maintenant ou programmé, par lots de 500 joueurs, suivi de progression, annulation |
| Notifications auto | Rappel du matin, relances et notifications d'action : activation, réglages, textes, test sur ses appareils |

## Notifications automatiques

Deux familles, définies dans `App\Services\Notifications\PushCatalog`. L'admin modifie les réglages et les textes, qui sont enregistrés dans `push_automations`.

- **Actions**, envoyées juste après la réponse HTTP, sans attendre le Cron et sans plafond : demande d'ami, ami accepté, défi reçu, résultat de défi, signalement corrigé, cadeau de l'équipe (case « Prévenir le joueur » de la fiche joueur).
- **Relances**, envoyées par le planificateur :

  | Relance | Quand | Règle |
  | --- | --- | --- |
  | Rappel du matin | 8 h | Rien si le joueur a joué dans la journée. Le texte dépend de sa situation : nouveau joueur, série en cours, question du matin, absent depuis plus de 8 jours (un rappel tous les 3 jours), endormi après 31 jours (un rappel par semaine). Plus rien après 90 jours. |
  | Série en danger | 19 h | Série d'au moins 2 jours et pas encore joué aujourd'hui. |
  | Vies rechargées | Toutes les 10 min | Joueur tombé à 0 vie, une fois toutes ses vies revenues. |
  | Coffre à ouvrir | 17 h | Coffre disponible depuis plus de 24 h. Une fois par coffre. |
  | Défi sans réponse | Toutes les heures | Défi reçu il y a plus de 24 h et pas joué. Une fois par défi. |
  | Fin de saison de ligue | Toutes les heures | 24 h avant la fin. Une fois par saison. |

  Les relances respectent deux règles communes : 2 relances au maximum par joueur et par jour, et aucune relance pendant les heures calmes (21 h – 7 h, heure d'Abidjan, c'est-à-dire UTC). Chaque envoi est journalisé dans `push_logs`, qui est purgée après 90 jours. Ce journal sert au plafond, à éviter les doublons et aux statistiques.

Côté app, `data.type` indique l'écran à ouvrir : `daily_reminder`, `streak`, `lives`, `chests`, `league`, `challenge` (avec `code`), `friend_request`, `friend_accepted`, `report_fixed`, `gift`, `admin_broadcast`. Les types que l'app ne connaît pas ouvrent l'accueil.

## Planificateur et file d'attente (LWS)

Les envois programmés, les envois groupés et les relances ne partent que si le planificateur tourne. Dans cPanel, ouvrir **Tâches Cron**, choisir « Une fois toutes les cinq minutes » et saisir la commande affichée dans l'admin (page Notifications auto, encart « Planificateur ») :

```
/usr/local/bin/php /home/c2860566c/public_html/ivoireQuizz_laravel/ivoirQuizz/artisan schedule:run >> /dev/null 2>&1
```

- À chaque passage, `notifications:tick` lance les envois dus. Si `QUEUE_CONNECTION=database`, un `queue:work --stop-when-empty` de 4 minutes au maximum envoie ensuite les lots.
- Avec un Cron toutes les 5 minutes :
  - les notifications d'action et l'envoi test partent tout de suite ;
  - les relances à heure fixe partent à l'heure pile ;
  - un envoi groupé ou programmé part dans les 5 minutes ;
  - « Vies rechargées » arrive 5 à 15 minutes après la recharge.

  Un Cron chaque minute fonctionne aussi.
- **Recommandé en production** : mettre `QUEUE_CONNECTION=database` dans le `.env`, puis lancer `php artisan config:cache` si la configuration est en cache. Avec `sync`, tout fonctionne aussi, mais les envois partent pendant la requête ou le passage du Cron, ce qui est plus lent pour les grosses audiences.
- L'encart « Planificateur » passe au vert au premier passage. S'il reste rouge, vérifier la commande Cron et la version de `/usr/local/bin/php -v`, qui doit être 8.2 ou plus. Si cPanel utilise un autre binaire PHP, le renseigner dans `CRON_PHP_BINARY` du `.env`.

## Règles de cohérence

- XP, score et progression d'un joueur sont calculés par le jeu : ils ne sont pas modifiables. Les pièces et gemmes s'ajustent avec un motif, et chaque ajustement crée une ligne `reward_transactions` (`source_type = admin`, `source_id` = l'administrateur).
- Une région, une ville, un niveau ou une catégorie qui contient encore des éléments ne peut pas être supprimé : il faut le désactiver.
- Les questions sont archivées (soft delete) pour conserver l'historique des parties. Les réponses existantes sont modifiées en place pour garder le lien avec `game_session_answers`.
- Une question importée d'un fichier de campagne garde ses modifications tant que son JSON ne change pas. Si le JSON change, le prochain import crée une nouvelle version et désactive l'ancienne.
