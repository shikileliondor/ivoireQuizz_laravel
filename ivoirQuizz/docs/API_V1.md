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
{ "name": "Azziz", "email": "azziz@test.com", "phone": "0700000000", "password": "password123", "password_confirmation": "password123" }
```
Succès: retourne `user` et `token`.

### POST /auth/login
Body:
```json
{ "email": "azziz@test.com", "password": "password123" }
```
Succès: retourne `user` et `token`.

### POST /auth/logout
Révoque le token courant.

### GET /me
Retourne le profil joueur.

## Carte et progression

### GET /game/map
Retourne régions, villes, niveaux actifs, progression utilisateur et position courante. Les bonnes réponses ne sont jamais retournées.

### GET /regions
Liste des régions.

### GET /regions/{region}
Détail région avec villes, niveaux et progression.

### GET /cities/{city}
Détail ville avec niveaux et progression.

### GET /levels/{level}
Détail niveau sans réponses correctes.

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
Query: `limit` (défaut/max 50). Retourne rang, nom, avatar et XP; jamais email/téléphone des autres joueurs.

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
