# IvoireQuiz API V1

Base URL: `/api/v1`

Toutes les réponses suivent le format standard:

```json
{ "success": true, "message": "Succès", "data": {} }
```

```json
{ "success": false, "message": "Erreur", "errors": {} }
```

## Authentification

Utiliser `Authorization: Bearer <token>` pour tous les endpoints sauf `POST /auth/register` et `POST /auth/login`.

### POST /auth/register
Body:
```json
{ "pseudo": "Azziz", "name": "Azziz", "username": "azziz", "email": "azziz@test.com", "phone": "0700000000", "password": "password123", "password_confirmation": "password123" }
```
`pseudo` ou `name` est obligatoire ; sans `pseudo`, il est calculé à partir de `name`. Règles : voir « Pseudo public » dans `docs/API.md`.
Succès: retourne `user` (avec `pseudo`) et `token`.

### POST /auth/login
Body:
```json
{ "email": "azziz@test.com", "password": "password123" }
```
Succès: retourne `user` et `token`.

### POST /auth/logout
Révoque le token courant.

### GET /me
Retourne le profil joueur, dont `pseudo`.

### PUT /me
Body: `{ "pseudo": "Azziz-07" }`. Modifie le pseudo public (3 à 15 lettres, chiffres, `_` ou `-` ; pas unique). Retourne l'utilisateur au même format que `GET /me`, ou `422` avec « Le pseudo doit contenir 3 à 15 caractères (lettres, chiffres, _ ou -). »

## Carte et progression

### GET /game/map
Retourne régions, villes, niveaux actifs, progression utilisateur et position courante. Les bonnes réponses ne sont jamais retournées.

Chaque ville inclut `image_url`, une URL absolue HTTPS vers son image ou `null` si aucun fichier n'est disponible. Les images sont servies depuis le disque Laravel `public`.

Extrait de réponse :
```json
{
  "success": true,
  "message": "Carte du jeu.",
  "data": {
    "regions": [{
      "cities": [{
        "id": 3,
        "name": "Yopougon",
        "image_url": "https://laravelquizz.studiobeyam.net/storage/cities/yopougon.jpg"
      }]
    }],
    "current_position": {"region_id": 1, "city_id": 3, "level_id": 12}
  }
}
```

Une ville sans image retourne `"image_url": null` et reste incluse dans la carte. Les images des villes sont envoyées depuis l'admin (fiche ville) et stockées dans `storage/app/public/cities/`, chemin enregistré dans `cities.image`. Les fichiers du disque `public` sont servis sous `/storage/…` : directement par Apache si le lien `public/storage` existe (`php artisan storage:link`), sinon par Laravel (`PublicStorageController`), ce qui est le cas sur l'hébergement FTP. Voir `docs/CITY_IMAGE_CREDITS.md` pour les sources et licences.

### GET /regions
Liste des régions.

### GET /regions/{region}
Détail région avec villes, niveaux et progression.

### GET /cities/{city}
Détail ville avec niveaux et progression.

### GET /levels/{level}
Détail niveau sans réponses correctes. La réponse inclut aussi `city`, la ressource de la ville du niveau avec son `image_url`.

## Sessions de jeu

### POST /levels/{level}/start
Body:
```json
{ "mode": "level" }
```
Vérifie vies et déblocage, crée la session et retourne questions/réponses sans `is_correct`.

### GET /game-sessions/history
Query: `page`, `per_page`, `status`, `mode`. Retourne l'historique paginé.

### GET /game-sessions/{session}
Retourne une session appartenant au joueur authentifié.

### POST /game-sessions/{session}/answer
Body:
```json
{ "question_id": 1, "answer_id": 3, "response_time": 8 }
```
Succès:
```json
{ "success": true, "message": "Réponse enregistrée.", "data": { "is_correct": true, "correct_answer_id": 3, "points_earned": 10, "xp_earned": 5, "explanation": "...", "session_score": 120 } }
```
Le backend calcule `is_correct`, points et XP.

### POST /game-sessions/{session}/finish
Termine une session active si toutes les questions ont reçu une réponse. Retourne score, accuracy, récompenses et statut.

### POST /game-sessions/{session}/abandon
Marque la session `abandoned`; aucune récompense.

## Vies et streak

### GET /lives
Retourne `lives`, `max_lives`, `next_life_at`, `can_play` après régénération.

### GET /streak
Retourne `current_streak`, `longest_streak`, `last_played_date`, `streak_freezes`.

## Ligue

### GET /league/current
Retourne ligue, saison, XP et rang du joueur.

### GET /league/ranking
Query: `limit` (défaut/max 50). Retourne rang, nom, pseudo, avatar et XP; jamais email/téléphone des autres joueurs.

## Coffres

### GET /chests
Query optionnel: `status=available|opened|locked`.

### POST /chests/{userChest}/open
Ouvre un coffre disponible appartenant au joueur. Retourne `xp`, `coins`, `gems`, `collectible`.

## Collection

### GET /collection
Retourne personnalités et monuments avec statut `is_unlocked`, rareté, région/ville.

### GET /collection/personalities
Filtre personnalités.

### GET /collection/monuments
Filtre monuments.

## Passeport

### GET /passport
Retourne les tampons de régions obtenus, `completed_at` et progression disponible par région.

## Signalement de question

### POST /questions/{question}/report
Body:
```json
{ "reason": "wrong_answer", "message": "La bonne réponse semble incorrecte." }
```
Reasons: `wrong_answer`, `typo`, `inappropriate`, `duplicate`, `other`. Un même utilisateur ne peut pas spammer la même question dans une fenêtre de 24h.

## Mode Chrono et aides en pièces

Les nouvelles parties chronométrées utilisent `mode=chrono`. La réponse de démarrage contient
`seconds_per_question` et `helpers`, copie immuable des réglages définis dans le panel admin.
Les anciennes sessions `mixed` restent lisibles pour préserver l'historique.

### POST /game-sessions/{session}/questions/{question}/hint

Achète l'indice de la question active. Le prix est déterminé par le serveur. En mode Chrono,
`timer_paused_until` indique jusqu'à quand le frontend peut arrêter le compteur.

```json
{
  "success": true,
  "data": {
    "hint": "Cette ville est située au centre du pays.",
    "cost": 30,
    "coins_balance": 470,
    "timer_paused_until": "2026-11-05T12:00:05.000000Z"
  }
}
```

### POST /game-sessions/{session}/questions/{question}/skip

Débite le prix configuré et termine la question avec le statut `skipped`, sans point ni XP.

```json
{
  "success": true,
  "data": { "status": "skipped", "cost": 50, "coins_balance": 420 }
}
```

Ces routes exigent Sanctum. Une aide déjà utilisée ou une question terminée retourne `409` ;
un solde insuffisant retourne `422`. Le client n'envoie jamais le prix ni le nouveau solde.

## Rate limiting

- Auth: 5/min
- API authentifiée: 120/min
- Start game: 10/min
- Answer quiz: 30/min
- Open chest: 10/min
- Report question: 5/min

## Notes sécurité

- Sanctum protège toutes les routes sauf register/login.
- Ownership vérifié pour sessions et coffres.
- Flutter n'envoie jamais score, XP, coins, gems, accuracy ou `is_correct`.
- `AnswerResource` ne retourne pas `is_correct`.
- Les tentatives suspectes sont loggées côté backend.
