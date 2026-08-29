# Briefing pour l'agent IA Flutter — changements backend

> **Destinataire :** l'agent qui développe le client Flutter IvoireQuiz.
> **Date :** 2 août 2026
> **Portée :** tout ce qui a changé côté Laravel et qui impose une action côté mobile.

Ce document est prescriptif. Chaque section indique **ce qui a changé**, **pourquoi**, et **ce que tu dois modifier dans l'app**. La référence complète des endpoints reste [FLUTTER_INTEGRATION.md](FLUTTER_INTEGRATION.md).

---

## Résumé exécutable

| # | Changement | Action Flutter | Priorité |
| --- | --- | --- | --- |
| 1 | `GET /auth/me` n'existe pas | Corriger en `GET /me` | **Bloquant** |
| 2 | Plus de chrono sur les niveaux normaux | Masquer le compte à rebours hors boss | Haute |
| 3 | Plus de perte de vie sur un niveau normal | Ne plus bloquer l'entrée à 0 vie | Haute |
| 4 | Rejouer un niveau réussi rend une vie | Proposer la révision quand les cœurs sont bas | Moyenne |
| 5 | `image` et `audio` désormais envoyés | Gérer les questions média | Moyenne |
| 6 | Noms de champs contre-intuitifs | Vérifier le mapping des modèles | **Bloquant si mal mappé** |
| 7 | **L'API amis n'existait pas — elle existe maintenant** | Intégrer les 6 endpoints | Haute |

---

## 1. La route du profil n'est pas sous `/auth` — BLOQUANT

C'est l'erreur `404 Route non trouvée` observée au lancement de l'app.

```
❌ GET /api/v1/auth/me     → 404 "Route non trouvée"
✅ GET /api/v1/me          → 200
```

**Pourquoi ce piège :** `login`, `register`, `logout`, `forgot-password` et `reset-password` sont tous sous `/auth/`. Le profil, lui, est à la racine. La symétrie apparente est trompeuse.

**Action :**

```dart
// ❌ Avant
final me = await api.get('/auth/me');

// ✅ Après
final me = await api.get('/me');
```

Vérifie aussi `POST /me/intro-seen` (et non `/auth/intro-seen`).

---

## 2. Le chrono ne s'applique plus qu'aux boss

### Ce qui a changé

Auparavant, chaque question avait 20 secondes et le score augmentait avec la rapidité. Désormais :

| Mode de session | Chrono appliqué | Bonus de vitesse |
| --- | --- | --- |
| `level`, `review` | **Non** | **Non** |
| `boss`, `daily_challenge` | Oui | Oui |

Sur un niveau normal, une bonne réponse rapporte le même nombre de points qu'elle soit donnée en 2 secondes ou en 60.

### Pourquoi

Le jeu vend la découverte de la Côte d'Ivoire. Un bonus de vitesse sur chaque question payait le joueur pour ignorer les explications — c'est-à-dire pour ignorer le produit lui-même. La pression appartient aux moments où elle est un plaisir, pas à ceux où l'on apprend.

### Action Flutter

1. **Lis `data.mode`** dans la réponse de `POST /levels/{id}/start`.
2. Si `mode` vaut `level` ou `review` → **n'affiche aucun compte à rebours**.
3. Si `mode` vaut `boss` ou `daily_challenge` → affiche le compte à rebours basé sur `time_limit`.

```dart
const timedModes = {'boss', 'daily_challenge'};
final isTimed = timedModes.contains(session.mode);

if (isTimed) {
  showCountdown(question.timeLimit);
}
```

### Points d'attention

- Le champ `time_limit` **reste présent** sur toutes les questions. Sa présence ne signifie pas qu'il s'applique. Fie-toi au `mode` de la session, jamais à la présence du champ.
- Continue d'envoyer un `response_time` réel dans `POST /answer` : il alimente les statistiques du back-office.
- En mode non chronométré, le serveur accepte jusqu'à **3600 secondes**. Au-delà, il refuse en 422. Ne renvoie donc pas une valeur aberrante si l'app est restée en arrière-plan — plafonne à 3600 côté client.
- En mode chronométré, dépasser `time_limit` provoque toujours un **422**. La logique existante reste valable.

---

## 3. Les vies ne se perdent plus sur un niveau normal

### Ce qui a changé

| Situation | Avant | Maintenant |
| --- | --- | --- |
| Rater un niveau normal | −1 vie | **Aucune perte** |
| Rater un boss | −1 vie | −1 vie *(inchangé)* |
| Démarrer un niveau normal à 0 vie | Refusé (422) | **Autorisé** |
| Démarrer un boss à 0 vie | Refusé (422) | Refusé (422) *(inchangé)* |

### Pourquoi

Un débutant se trompe forcément, puisqu'il découvre le contenu. L'ancienne règle l'enfermait dehors pendant 2 h 30 dès sa première session : 10 questions, 70 % de réussite exigée, donc 3 erreurs = échec = vie perdue, cinq fois de suite. C'est le moyen le plus sûr de ne jamais le revoir.

### Action Flutter — importante

**Ne bloque plus l'entrée d'un niveau normal quand `stats.lives == 0`.**

```dart
// ❌ À supprimer
if (stats.lives == 0) {
  showOutOfLivesDialog();
  return;
}

// ✅ Remplacer par
if (node.isBoss && stats.lives == 0) {
  showOutOfLivesDialog();   // seul le boss exige une vie
  return;
}
```

La barre de cœurs reste affichée — elle devient informative, et ne barre la route que devant un boss.

Le serveur reste la source de vérité : s'il refuse, il renvoie un 422 avec un message affichable.

---

## 4. Rejouer un niveau déjà réussi rend une vie

### Ce qui a changé

Terminer avec succès un niveau **déjà terminé auparavant** ajoute **+1 vie** (plafonnée au maximum de 5). Un premier succès sur un niveau ne donne rien : seule la révision soigne.

### Pourquoi

L'attente passive de la régénération (30 min par vie) devient de la révision active. C'est bon pour la rétention et pour l'apprentissage — le joueur rejoue le contenu qu'il connaît mal au lieu de fermer l'app.

### Action Flutter

Rien n'est obligatoire : le serveur applique la règle seul, et `GET /game/map` renvoie le nouveau total dans `stats.lives`.

Mais c'est une **opportunité produit** : quand `stats.lives` est bas, propose explicitement

> « Refais un niveau terminé pour regagner une vie »

en mettant en avant les nœuds `is_completed: true` de `path_nodes`.

---

## 5. Les questions média sont enfin exploitables

### Ce qui a changé

`QuestionResource` n'envoyait **ni `image` ni `audio`**. Une question de type `image` arrivait donc dans l'app sans son média : un écran vide et sans réponse possible. Les deux champs sont désormais inclus.

### Format actuel

```json
{
  "id": 501,
  "level_id": 12,
  "category_id": 3,
  "question": "Quel monument est-ce ?",
  "type": "image",
  "image": "questions/basilique.jpg",
  "audio": null,
  "difficulty": "easy",
  "points": 10,
  "xp_reward": 5,
  "time_limit": 20,
  "answers": [
    { "id": 2001, "question_id": 501, "text": "La Basilique de Yamoussoukro", "order": 0 }
  ]
}
```

### Action Flutter

Gère les trois valeurs de `type` :

| `type` | Rendu attendu |
| --- | --- |
| `text` | Énoncé seul |
| `image` | Énoncé + image chargée depuis `image` |
| `audio` | Énoncé + lecteur audio sur `audio` |

Les chemins sont **relatifs** au stockage Laravel : préfixe-les avec l'URL de base des médias.

Le back-office refuse désormais de créer une question `image` sans image (et `audio` sans audio), donc le cas « type média sans fichier » ne peut plus arriver pour du contenu neuf. Prévois quand même un repli si `image`/`audio` est `null`.

---

## 6. Pièges de nommage — vérifie ton mapping

Les noms des champs de l'API joueur **ne correspondent pas** aux colonnes de la base ni à l'API admin. C'est la source de bug la plus probable.

| Concept | API joueur | Base de données / API admin |
| --- | --- | --- |
| Énoncé de la question | **`question`** | `question_text` |
| Libellé d'une réponse | **`text`** | `answer_text` |

```dart
// ✅ Mapping correct côté Flutter
factory Question.fromJson(Map<String, dynamic> json) => Question(
  id: json['id'],
  text: json['question'],          // ⚠️ pas 'question_text'
  type: json['type'],
  image: json['image'],
  audio: json['audio'],
  timeLimit: json['time_limit'],
  answers: (json['answers'] as List).map(Answer.fromJson).toList(),
);

factory Answer.fromJson(Map<String, dynamic> json) => Answer(
  id: json['id'],
  label: json['text'],             // ⚠️ pas 'answer_text'
);
```

**La bonne réponse n'est jamais présente** dans le payload de `start`. C'est volontaire : elle n'arrive qu'après avoir répondu.

---

## 7. Rappel — l'explication était déjà là, sers-t'en

Aucun changement ici, mais c'est le point le plus important du jeu et il est souvent ignoré.

`POST /game-sessions/{id}/answer` renvoie :

```json
{
  "success": true,
  "message": "Réponse enregistrée.",
  "data": {
    "is_correct": false,
    "points_earned": 0,
    "xp_earned": 0,
    "correct_answer_id": 2003,
    "explanation": "Yamoussoukro est capitale politique depuis 1983.",
    "session_score": 40
  }
}
```

**Comportement attendu, en moins de 100 ms :**

1. Bonne réponse → vert, son, XP qui s'envole, compteur de combo
2. Mauvaise réponse → rouge, puis surligner `correct_answer_id` en vert
3. **Dans les deux cas** → carte « Le saviez-vous ? » avec `explanation` et un bouton **Compris**

Surtout **pas d'enchaînement automatique**. Ce bouton force la lecture, et c'est cette carte qui transforme une erreur en découverte. C'est aussi le contenu que les joueurs capturent et partagent sur WhatsApp — le principal levier d'acquisition du jeu.

Une question ne peut recevoir qu'**une seule réponse** par session (422 sinon). N'autorise pas le retour en arrière.

---

## 7 bis. L'API amis — nouvelle, elle n'existait pas

### Ce qui a changé

La table `friendships` et le modèle `Friendship` existaient depuis le début, mais **aucune route ne les exposait**. Tout appel à un endpoint ami renvoyait `404 Route non trouvée`. Six endpoints ont été ajoutés.

### Les endpoints

| Méthode | Route | Rôle |
| --- | --- | --- |
| GET | `/api/v1/friends` | Ton code ami + la liste de tes amis |
| GET | `/api/v1/friends/requests` | Demandes en attente, reçues **et** envoyées |
| GET | `/api/v1/friends/leaderboard` | **Classement entre amis, toi inclus** |
| POST | `/api/v1/friends/request` | Envoyer une demande via un code ami |
| POST | `/api/v1/friends/{id}/accept` | Accepter une demande reçue |
| DELETE | `/api/v1/friends/{id}` | Refuser une demande ou retirer un ami |

### `GET /friends`

```json
{
  "data": {
    "friend_code": "K3M9PZ",
    "friends": [
      { "id": 42, "name": "Awa", "avatar": null, "avatar_id": 3,
        "friend_code": "ABC123", "xp_total": 1250, "total_score": 8400,
        "current_level": 7, "games_played": 61, "games_won": 48 }
    ]
  }
}
```

`friend_code` est **le tien** : affiche-le en grand, avec un bouton « Partager ». C'est six caractères, lisibles à l'oral et copiables dans un message WhatsApp.

Le payload d'un ami ne contient **jamais** son e-mail ni son téléphone. C'est vérifié par un test.

### `POST /friends/request`

```json
{ "friend_code": "ABC123" }
```

Le code est normalisé côté serveur (majuscules, espaces retirés) — tu peux envoyer la saisie brute de l'utilisateur.

Réponses possibles, toutes affichables telles quelles :

| Cas | Code | `message` |
| --- | --- | --- |
| Demande envoyée | 201 | `Demande envoyée.` |
| **L'autre t'avait déjà demandé** | 201 | `Vous êtes maintenant amis.` |
| Code inconnu | 422 | `Aucun joueur ne correspond à ce code ami.` |
| Ton propre code | 422 | `Tu ne peux pas t'ajouter toi-même.` |
| Déjà demandé | 422 | `Ta demande est déjà en attente.` |
| Déjà amis | 422 | `Vous êtes déjà amis.` |
| Format invalide | 422 | `errors.friend_code` — 6 caractères alphanumériques |

**Cas important :** si le joueur B t'avait déjà envoyé une demande et que tu envoies la tienne, le serveur **accepte directement** — tu obtiens `status: "accepted"` en 201. Pas de demande croisée en double. Regarde `data.status` pour savoir quel écran afficher.

Limité à **10 demandes par minute**.

### `GET /friends/requests`

```json
{
  "data": {
    "received": [ { "id": 8, "status": "pending", "direction": "received",
                    "requester": { "id": 42, "name": "Awa", "…": "…" },
                    "created_at": "2026-08-03T10:12:00+00:00" } ],
    "sent": [ … ]
  }
}
```

Le champ **`direction`** (`sent` / `received`) t'évite de comparer des identifiants pour savoir dans quel sens va la relation. Utilise-le.

Seules les demandes de `received` peuvent être acceptées. Un `accept` sur une demande que tu as envoyée renvoie 422 : *« Seul le destinataire peut accepter cette demande. »*

### `GET /friends/leaderboard` — le plus important

```json
{
  "data": [
    { "rank": 1, "is_me": false, "player": { "name": "Awa", "xp_total": 1250, "…": "…" } },
    { "rank": 2, "is_me": true,  "player": { "name": "Moi", "xp_total": 900, "…": "…" } },
    { "rank": 3, "is_me": false, "player": { "name": "Koffi", "xp_total": 400, "…": "…" } }
  ]
}
```

Le joueur **est inclus** dans le classement, marqué par `is_me: true` — surligne sa ligne. Un joueur sans aucun ami reçoit une liste d'un seul élément, lui-même : ce n'est pas un cas d'erreur, affiche-le avec une invitation à ajouter des amis.

Le tri se fait sur `xp_total`, puis `total_score` en cas d'égalité.

**Pourquoi cet écran compte plus que les ligues.** Les ligues classent le joueur face à des inconnus. Ici, la comparaison porte sur des gens qu'il connaît — c'est ça qui fait revenir. Un « Défi de la semaine » envoyé par lien WhatsApp (*« J'ai fait 8/10 sur Cocody, fais mieux »*) vaut dix ligues Bronze, et c'est aussi le principal canal d'acquisition du jeu.

### `DELETE /friends/{id}`

Une seule route pour deux gestes : **refuser** une demande en attente et **retirer** un ami. Les deux côtés de la relation peuvent la supprimer. Un tiers qui essaie reçoit 422.

### Écrans à construire

1. **Mes amis** — ton code ami en évidence avec bouton Partager, puis la liste
2. **Ajouter un ami** — un champ de 6 caractères, en majuscules automatiques
3. **Demandes** — badge sur `received.length`, boutons Accepter / Refuser
4. **Classement entre amis** — ta ligne surlignée via `is_me`

---

## 8. Réponse de fin de partie

`POST /game-sessions/{id}/finish` renvoie exactement :

```json
{
  "data": {
    "score": 120,
    "accuracy": 80.0,
    "correct_answers": 8,
    "wrong_answers": 2,
    "xp_earned": 90,
    "coins_earned": 10,
    "gems_earned": 0,
    "status": "completed",
    "rewards": { "xp": 90, "coins": 10, "gems": 0 }
  }
}
```

- `status` vaut `completed` ou `failed`
- Étoiles à déduire de `accuracy` : **≥ 90 % = 3**, **≥ 75 % = 2**, **≥ passing_score = 1**
- **Rappelle systématiquement `GET /game/map` après un `finish`** : le niveau suivant vient d'être déverrouillé, les stats ont bougé, un coffre ou un tampon de passeport a pu apparaître

À appeler seulement quand **toutes** les questions ont reçu une réponse — le serveur refuse un décompte partiel.

---

## 9. Ce qui n'a pas changé

Pour éviter tout travail inutile :

- Toutes les autres routes joueur sont **identiques**
- L'enveloppe `{ success, message, data }` est **identique**
- Les codes d'erreur (401 / 404 / 422 / 429 / 500) sont **identiques**
- L'authentification Sanctum par Bearer token est **identique**
- La structure de `GET /game/map` est **identique**
- La régénération des vies reste à **1 vie / 30 minutes**, maximum 5

Un back-office React a été ajouté côté serveur (`/api/v1/admin/*`), réservé aux comptes `role = admin`. **Flutter ne doit jamais appeler ces routes.**

---

## Checklist d'intégration

- [ ] `GET /auth/me` remplacé par `GET /me` *(débloque le démarrage de l'app)*
- [ ] Le compte à rebours n'apparaît que si `session.mode` ∈ `{boss, daily_challenge}`
- [ ] L'entrée d'un niveau normal n'est plus bloquée quand `lives == 0`
- [ ] Seul un boss exige une vie côté client
- [ ] `response_time` plafonné à 3600 en mode non chronométré
- [ ] `question` et `text` correctement mappés (et non `question_text` / `answer_text`)
- [ ] `image` et `audio` gérés selon `type`
- [ ] Carte explication avec bouton **Compris** obligatoire après chaque réponse
- [ ] `GET /game/map` rappelé après chaque `finish`
- [ ] Suggestion « regagne une vie en révisant » quand les cœurs sont bas
- [ ] Écran « Mes amis » avec le `friend_code` du joueur en évidence
- [ ] Ajout d'un ami par code (6 caractères, majuscules automatiques)
- [ ] Demandes reçues / envoyées séparées via le champ `direction`
- [ ] Classement entre amis avec la ligne `is_me` surlignée
- [ ] Cas « demande croisée » géré : un `POST /friends/request` peut renvoyer `status: "accepted"`

---

## Références

- [FLUTTER_INTEGRATION.md](FLUTTER_INTEGRATION.md) — guide complet du client mobile
- [API_V1.md](API_V1.md) — référence des endpoints joueur
- [GAMEPLAY.md](GAMEPLAY.md) — les règles côté produit
