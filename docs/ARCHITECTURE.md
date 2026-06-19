# Architecture globale

## Vue d’ensemble

```text
Flutter Mobile
      ↓ HTTPS JSON
Laravel API V1
      ↓ Controllers / Requests / Resources
Services métier
      ↓ Eloquent transactions
MySQL source de vérité
      ↓ accélération optionnelle
Redis cache / queues / classements
```

Le mobile Flutter consomme uniquement l’API. Laravel valide les entrées, applique les règles métier et persiste l’état. Redis accélère certaines lectures ou classements, mais MySQL reste l’état officiel du jeu.

## Dossiers importants

| Dossier | Rôle |
| --- | --- |
| `app/Models` | Modèles Eloquent : `User`, `Region`, `Level`, `GameSession`, `RewardTransaction`, etc. |
| `app/Services/Game` | Logique métier : sessions, réponses, fin de partie, récompenses, vies, streak, progression, ligues, coffres, collections, passeport, cache. |
| `app/Http/Controllers/Api/V1` | Contrôleurs API V1. Ils reçoivent la requête, appellent les services et retournent les resources. |
| `app/Http/Requests/Api/V1` | FormRequests de validation JSON pour les payloads mobiles. |
| `app/Http/Resources/Api/V1` | Resources Laravel responsables du formatage des réponses. |
| `database/migrations` | Schéma MySQL versionné. |
| `database/seeders` | Données initiales : régions, villes, niveaux, questions, ligues, coffres, collections. |
| `routes/api.php` | Déclaration des routes `/api/v1`, middlewares Sanctum et rate limiting. |
| `docs/` | Documentation technique backend et Flutter. |

## Principes de conception

### Contrôleurs minces

Les contrôleurs ne doivent pas contenir de règles gameplay complexes. Ils doivent :

1. recevoir la requête ;
2. utiliser un FormRequest si nécessaire ;
3. vérifier l’utilisateur authentifié ;
4. appeler un service métier ;
5. retourner une Resource ou une réponse JSON standard.

### Logique métier dans les services

Les règles de jeu sont centralisées dans `app/Services/Game` pour être testables et réutilisables. Par exemple, `FinishGameSessionService` orchestre la fin de partie, tandis que `RewardService` applique les gains/pertes.

### Validation dans les FormRequests

Flutter ne doit pas être considéré comme fiable. Les FormRequests valident les champs attendus et Laravel retourne une réponse `422` standardisée en cas d’erreur.

### Formatage dans les Resources

Les Resources masquent les détails internes des modèles et exposent un format stable pour Flutter.

### MySQL source de vérité

Toutes les données critiques sont persistées en MySQL : utilisateurs, sessions, réponses, récompenses, progressions, coffres et collections.

### Redis accélération uniquement

Redis peut contenir :

- le cache de la carte de jeu ;
- les jobs de queue ;
- les classements rapides des ligues ;
- éventuellement des compteurs de rate limiting.

Redis peut être vidé sans corrompre le jeu : les données critiques doivent être reconstruites depuis MySQL.
