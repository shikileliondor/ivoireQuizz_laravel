# API IvoirQuizz — Phase 1

Base URL : `/api/v1`

## Connexion Google (Flutter)

`POST /auth/google` est public et soumis à la limite `5/minute` par adresse IP. Flutter envoie le **ID token** Google obtenu après la connexion, jamais le simple identifiant de compte ni un access token.

```json
{ "id_token": "<Google ID token>" }
```

Le serveur vérifie la signature, l'expiration et l'audience du jeton. Il retrouve ou crée le compte et retourne `data.user`, `data.token`, `data.token_type` (`Bearer`) et `data.created`. Le client conserve le token Sanctum et l'envoie ensuite avec `Authorization: Bearer <token>`.

Configurer `GOOGLE_CLIENT_ID` sur le serveur avec le Client ID OAuth **Web** utilisé comme audience serveur par l'application Flutter (`serverClientId`). Ce Client ID est public; aucun secret OAuth ne doit être embarqué dans l'application mobile. Les ID tokens d'une autre audience sont refusés.

```dart
final response = await http.post(
  Uri.parse('$baseUrl/api/v1/auth/google'),
  headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
  body: jsonEncode({'id_token': idToken}),
);
final body = jsonDecode(response.body) as Map<String, dynamic>;
final apiToken = body['data']['token'] as String;
```

Le projet Flutter doit aussi déclarer les identifiants OAuth Android correspondant au package ID et aux empreintes de ses builds. Il n'est pas présent dans ce dépôt.

Les routes protégées utilisent l’en-tête `Authorization: Bearer <token>` et acceptent du JSON.

## Format des réponses

Succès :

```json
{
  "success": true,
  "message": "Opération réussie.",
  "data": {}
}
```

Erreur :

```json
{
  "success": false,
  "message": "Les données fournies sont invalides.",
  "errors": {
    "field": ["Message de validation."]
  }
}
```

Codes principaux : `200`, `201`, `401`, `403`, `404`, `422`, `429`.

## Pseudo public

Chaque joueur a un `pseudo` : le nom affiché aux autres joueurs (accueil, classements, amis, défis, ligue, notifications d'amis). L'app l'affiche partout et retombe sur le prénom (premier mot de `name`) s'il vaut `null`.

- Format : `/^[\p{L}\p{N}_-]{3,15}$/u`, soit 3 à 15 lettres (accents compris), chiffres, `_` ou `-`, sans espace.
- **Pas unique** : deux joueurs peuvent avoir le même pseudo. Le code ami reste l'identifiant d'un joueur.
- Erreur de validation (`422`) : `errors.pseudo[0]` vaut « Le pseudo doit contenir 3 à 15 caractères (lettres, chiffres, _ ou -). »
- Pseudo attribué automatiquement quand le client n'en envoie pas : premier mot de `name`, nettoyé des caractères interdits et coupé à 15 caractères. S'il reste moins de 3 caractères : `Joueur<id>` (ex. `Joueur42`).
- Les comptes supprimés (anonymisés) ont `pseudo: null`.

Champs `pseudo` renvoyés :

| Endpoint | Où |
| --- | --- |
| `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/v1/auth/register`, `POST /api/v1/auth/login`, `POST /api/v1/auth/google` | `data.user.pseudo` |
| `GET /api/auth/me`, `GET /api/v1/me`, `PUT /api/v1/me`, `PUT /api/v1/profile` | `data.pseudo` |
| `GET /api/home` | `data.user.pseudo` |
| `GET /api/leaderboard/global`, `/weekly`, `/cities/{city}` | `data.leaderboard.data[].pseudo` |
| `GET /api/leaderboard/friends` | `data.leaderboard[].pseudo` |
| `GET /api/friends` | `data.friends[].pseudo` |
| `GET /api/friends/requests` | `data.requests[].requester.pseudo` |
| `GET /api/challenges` | `data.challenges[].challenger_pseudo`, `opponent_pseudo` |
| `GET /api/challenges/{code}` | `data.challenger` et `data.opponent` : `{ id, name, pseudo, avatar_id }` |
| `GET /api/v1/league/ranking` | `data[].user.pseudo` |

### Inscription par l'app : `POST /api/auth/register`

```json
{ "pseudo": "Awa225", "email": "awa@example.com", "password": "password123", "password_confirmation": "password123" }
```

- `pseudo` : règles ci-dessus. `name` devient facultatif ; s'il manque, `name` prend la valeur du pseudo.
- Il faut au moins `pseudo` ou `name`.
- Les anciennes versions de l'app envoient seulement `name` : le pseudo est alors calculé à partir de `name` (voir ci-dessus).

### Connexion Google

À la création du compte uniquement, le pseudo est le prénom Google (`given_name`), nettoyé ; à défaut, le premier mot de `name` ; à défaut, `Joueur<id>`. Les connexions suivantes ne modifient jamais le pseudo, et un compte e-mail existant relié à Google garde le sien.

### Modifier son pseudo : `PUT /me`

Sanctum.

```json
{ "pseudo": "Awa-CI" }
```

Retour `200` : l'utilisateur mis à jour, au même format que `GET /me`. En cas d'erreur, `422` avec le message ci-dessus dans `message` et dans `errors.pseudo[0]`.

## Inscription

`POST /auth/register` — publique — limite `5/minute` par adresse IP.

```json
{
  "pseudo": "Awa225",
  "name": "Awa Koné",
  "username": "awa_kone",
  "email": "awa@example.com",
  "phone": "0700000000",
  "password": "password123",
  "password_confirmation": "password123"
}
```

- `pseudo` : voir « Pseudo public ». Obligatoire si `name` est absent ; sinon calculé à partir de `name`.
- `name` : facultatif si `pseudo` est fourni (prend alors la valeur du pseudo), chaîne, 100 caractères maximum.
- `username` : obligatoire, 3 à 50 caractères ASCII, lettres/chiffres/tirets/underscores, unique.
- `email` : obligatoire, valide, 191 caractères maximum, unique.
- `phone` : facultatif, 30 caractères maximum.
- `password` : au moins 8 caractères, avec lettres et chiffres, confirmation obligatoire.
- `xp`, `level`, `coins` et `status` sont interdits.

Retour `201` : utilisateur, token Sanctum et type `Bearer`.

## Connexion

`POST /auth/login` — publique — limite `5/minute` par adresse IP.

```json
{ "email": "awa@example.com", "password": "password123" }
```

Retour `200` : utilisateur et nouveau token. Les identifiants invalides retournent toujours la même erreur `401`. Un compte suspendu ou désactivé retourne `403`.

## Mot de passe oublié

`POST /auth/forgot-password` — publique — limite `3/minute` par couple email/IP et `20/heure` par IP.

```json
{ "email": "awa@example.com" }
```

La réponse `200` est volontairement identique, que l’adresse existe ou non. L’envoi est effectué avec une notification Laravel mise en queue.

## Réinitialisation

`POST /auth/reset-password` — publique — même limitation que la demande de lien.

```json
{
  "email": "awa@example.com",
  "token": "token-reçu",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

Après succès, tous les tokens API existants sont révoqués.

## Utilisateur connecté

`GET /me` — Sanctum.

Retourne le profil (dont `pseudo`), les valeurs serveur `level`, `xp`, `coins` et la progression vers le prochain niveau.

`PUT /me` modifie le pseudo : voir « Pseudo public ».

## Modification du profil

`PUT /profile` — Sanctum.

Tous les champs sont facultatifs : `name`, `pseudo`, `username`, `phone`, `avatar`, `city`, `bio`. `email`, `xp`, `xp_total`, `level`, `current_level`, `coins` et `status` sont interdits.

## Modification du mot de passe

`PUT /password` — Sanctum — limite d’authentification.

```json
{
  "current_password": "password123",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

Tous les tokens sont révoqués après succès : le mobile doit reconnecter le joueur.

## Déconnexion

- `POST /auth/logout` révoque le token courant.
- `POST /auth/logout-all` révoque tous les tokens du joueur.

## Suppression du compte

`DELETE /account` — Sanctum — limite d’authentification.

```json
{ "current_password": "password123", "confirmation": "SUPPRIMER" }
```

Les tokens sont révoqués, les données personnelles sont anonymisées, puis le compte est supprimé logiquement.

## Niveaux joueur

Les seuils sont définis dans `config/progression.php`. Le client ne peut pas modifier l’XP ou le niveau. `PlayerLevelService` recalcule automatiquement `current_level` lorsque `RewardService` accorde de l’XP.

## Test local

```bash
php artisan migrate
php artisan test
php artisan route:list --path=api/v1
```

Configurer `FRONTEND_URL` pour que les emails de réinitialisation pointent vers l’écran correspondant de l’application cliente. Une queue worker doit être active hors environnement de test.
