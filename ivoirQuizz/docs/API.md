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

Le projet Flutter doit aussi déclarer les identifiants OAuth Android/iOS correspondant aux package/bundle IDs et empreintes de ses builds. Il n'est pas présent dans ce dépôt.

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

## Inscription

`POST /auth/register` — publique — limite `5/minute` par adresse IP.

```json
{
  "name": "Awa Koné",
  "username": "awa_kone",
  "email": "awa@example.com",
  "phone": "0700000000",
  "password": "password123",
  "password_confirmation": "password123"
}
```

- `name` : obligatoire, chaîne, 100 caractères maximum.
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

Retourne le profil, les valeurs serveur `level`, `xp`, `coins` et la progression vers le prochain niveau.

## Modification du profil

`PUT /profile` — Sanctum.

Tous les champs sont facultatifs : `name`, `username`, `phone`, `avatar`, `city`, `bio`. `email`, `xp`, `xp_total`, `level`, `current_level`, `coins` et `status` sont interdits.

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
