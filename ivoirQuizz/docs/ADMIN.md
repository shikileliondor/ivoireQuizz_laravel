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

## Règles de cohérence

- XP, score et progression d'un joueur sont calculés par le jeu : ils ne sont pas modifiables. Les pièces et gemmes s'ajustent avec un motif, et chaque ajustement crée une ligne `reward_transactions` (`source_type = admin`, `source_id` = l'administrateur).
- Une région, une ville, un niveau ou une catégorie qui contient encore des éléments ne peut pas être supprimé : il faut le désactiver.
- Les questions sont archivées (soft delete) pour conserver l'historique des parties. Les réponses existantes sont modifiées en place pour garder le lien avec `game_session_answers`.
- Une question importée d'un fichier de campagne garde ses modifications tant que son JSON ne change pas. Si le JSON change, le prochain import crée une nouvelle version et désactive l'ancienne.
