# API Admin — back-office React

API consommée par le panel React (`frontend/`). Elle sert à produire et équilibrer le contenu du jeu, pas à y jouer.

**Le panel est avant tout une usine à questions.** Le seul chiffre qui compte pour cet outil : combien de secondes pour saisir une question. Tout le reste est secondaire.

---

## 1. Accès

Base : `/api/v1/admin` — **toutes** les routes exigent :

1. un token Sanctum valide (`Authorization: Bearer <token>`)
2. un utilisateur dont `role = 'admin'`

| Situation | Code | Message |
| --- | --- | --- |
| Pas de token | 401 | `Non authentifié.` |
| Token valide mais joueur normal | 403 | `Accès réservé aux administrateurs.` |

Toute tentative d'accès par un non-admin est journalisée (user id, chemin, IP).

### Se connecter
Il n'y a pas de login séparé : le panel utilise `POST /api/v1/auth/login` comme l'app. Le champ `role` n'est pas exposé par `/me` — le panel confirme les droits en appelant `GET /admin/dashboard` : `200` = admin, `403` = non.

### Créer le premier admin
```bash
php artisan db:seed --class=AdminSeeder
```
Compte : `admin@ivoirequiz.com`. **Changer ce mot de passe avant toute mise en ligne.** Ensuite, promouvoir d'autres comptes via `PATCH /admin/players/{id}` avec `{"role": "admin"}`.

### Quotas
300 requêtes/minute (contre 120 côté joueur) : la saisie de contenu est naturellement en rafale.

---

## 2. Format des réponses

Ressource unique :
```json
{ "success": true, "message": "Question créée.", "data": { } }
```

Listes paginées — **format Laravel standard**, sans l'enveloppe `success` :
```json
{ "data": [ ], "links": { }, "meta": { "current_page": 1, "last_page": 4, "total": 87 } }
```

Erreurs de validation (422) :
```json
{ "success": false, "message": "Les données fournies sont invalides.",
  "errors": { "answers": ["La question doit avoir exactement une bonne réponse."] } }
```

Paramètres communs aux listes : `per_page` (défaut 25, max 100), `page`, `search`.

---

## 3. Tableau de bord

### `GET /admin/dashboard`
```json
{
  "data": {
    "content": { "regions": 5, "chapters": 18, "levels": 94,
                 "questions": 714, "active_questions": 702,
                 "incomplete_levels": 7 },
    "players": { "total": 1204, "new_this_week": 88,
                 "active_today": 143, "active_this_week": 512 },
    "gameplay": { "sessions_today": 320, "sessions_this_week": 2100,
                  "completed_this_week": 1500, "failed_this_week": 400,
                  "abandoned_this_week": 200, "success_rate": 78.9 },
    "moderation": { "pending_reports": 12, "reports_this_week": 30 }
  }
}
```

**`incomplete_levels` est la métrique la plus importante du panel.** Ce sont les niveaux qui tirent plus de questions qu'ils n'en possèdent : ils **ne peuvent pas démarrer** et bloquent la progression des joueurs. Si ce chiffre n'est pas à zéro, c'est le premier chantier. À afficher en rouge.

### `GET /admin/stats/question-balance`
Params : `min_answers` (défaut 20), `limit` (défaut 20, max 50).

Renvoie `hardest` et `easiest` — les questions par taux de réussite, parmi celles suffisamment jouées.

C'est l'outil de réglage de la difficulté : une question à **5 % de réussite** est presque toujours mal formulée ou a une mauvaise clé de réponse, pas « difficile ». Une question à **98 %** n'apprend rien.

### `GET /admin/stats/level-funnel`
Les niveaux où les joueurs s'arrêtent, triés par abandons décroissants (minimum 5 tentatives).

```json
{ "data": [ { "id": 12, "title": "Le Plateau historique",
              "attempts": 340, "completed": 210, "abandoned": 95,
              "avg_accuracy": 62.4 } ] }
```

Un pic d'abandons est un problème de conception de niveau, jamais un problème de joueur.

---

## 4. Structure : régions, chapitres, niveaux

Même schéma CRUD pour les trois.

| Verbe | Route | Effet |
| --- | --- | --- |
| GET | `/admin/regions` | Liste paginée + `chapters_count`, `levels_count` |
| POST | `/admin/regions` | Création |
| GET | `/admin/regions/{id}` | Détail + chapitres |
| PUT/PATCH | `/admin/regions/{id}` | Mise à jour |
| DELETE | `/admin/regions/{id}` | **Archivage** (soft delete) |
| POST | `/admin/regions/reorder` | Réordonnancement |

Idem pour `/admin/chapters` et `/admin/levels`.

### Le slug est automatique
Ne jamais demander le slug à l'utilisateur. Omets le champ : il est dérivé du nom, et suffixé en cas de collision (`bouake`, puis `bouake-2`). Tu peux le forcer, mais il n'y a aucune raison de le faire.

### La suppression est un archivage
`DELETE` fait un soft delete. C'est volontaire : les tables de progression pointent sur ces lignes, et une suppression réelle effacerait l'historique de tous les joueurs qui y sont passés. Le panel doit dire « Archiver », pas « Supprimer ».

### Réordonnancement
```json
POST /admin/regions/reorder
{ "ids": [3, 1, 2] }
```
Envoie **la liste complète dans l'ordre voulu** — le serveur réécrit `order` à 1, 2, 3… Parfait pour un drag-and-drop.

### Filtres
- Chapitres : `region_id`, `search`, `is_active`
- Niveaux : `chapter_id`, `region_id`, `node_type`, `search`, `is_active`, **`incomplete_only=1`**

`incomplete_only=1` est le filtre à mettre en avant : il liste exactement les niveaux à réparer.

### Champs d'un niveau

| Champ | Note |
| --- | --- |
| `node_type` | `level` \| `chest` \| `boss` \| `review` — un `chest` n'est pas jouable |
| `is_boss` | **Doit être cohérent avec `node_type`** : `node_type=boss` ⇔ `is_boss=true`, sinon 422 |
| `questions_count` | Combien de questions le niveau **tire** par partie (1–50) |
| `passing_score` | Seuil de réussite en % (0–100) |
| `difficulty` | `easy` \| `medium` \| `hard` \| `expert` |

Chaque niveau renvoyé en détail porte son état de santé :

```json
{ "questions_count": 10, "available_questions": 6,
  "is_playable": false, "missing_questions": 4 }
```

**À afficher systématiquement.** `is_playable: false` = niveau cassé pour les joueurs.

---

## 5. Questions — l'écran central

### `GET /admin/questions`

Filtres : `level_id`, `chapter_id`, `region_id`, `category_id`, `difficulty`, `type`, `search`, `is_active`, `reported_only=1`.

Chaque question renvoie ses réponses, sa catégorie, son niveau, son nombre de signalements en attente, et ses statistiques :

```json
{ "id": 501, "question_text": "…", "type": "text", "difficulty": "easy",
  "explanation": "…", "points": 10, "xp_reward": 5, "time_limit": 20,
  "is_active": true, "pending_reports_count": 0,
  "answers": [ { "id": 2001, "answer_text": "Yamoussoukro", "is_correct": true, "order": 0 } ],
  "stats": { "times_answered": 340, "success_rate": 61.2 } }
```

> Ici les champs portent leurs noms de base (`question_text`, `answer_text`), contrairement à l'API joueur.

### `POST /admin/questions` — création

Une question et ses réponses partent **dans le même appel**, en une transaction.

```json
{
  "level_id": 12,
  "category_id": 3,
  "question_text": "Quelle est la capitale politique de la Côte d'Ivoire ?",
  "type": "text",
  "difficulty": "easy",
  "explanation": "Yamoussoukro est capitale politique depuis 1983.",
  "answers": [
    { "answer_text": "Yamoussoukro", "is_correct": true },
    { "answer_text": "Abidjan",      "is_correct": false },
    { "answer_text": "Bouaké",       "is_correct": false },
    { "answer_text": "San Pedro",    "is_correct": false }
  ]
}
```

Champs optionnels : `points` (défaut 10), `xp_reward` (5), `time_limit` (20), `is_active` (true).

**Règles refusées en 422 :**
- pas exactement **une** bonne réponse
- deux réponses au libellé identique (insensible à la casse et aux espaces)
- moins de 2 ou plus de 6 réponses
- `type: "image"` sans `image`, ou `type: "audio"` sans `audio`

### `PUT /admin/questions/{id}` — modification

Passe `id` sur les réponses à conserver :

```json
{ "answers": [
    { "id": 2001, "answer_text": "Yamoussoukro", "is_correct": true },
    { "answer_text": "Nouvelle option", "is_correct": false } ] }
```

Une réponse avec `id` est mise à jour, une sans `id` est créée, une absente est supprimée.

**Conserve les `id` autant que possible.** Ils sont référencés par `game_session_answers` : les recréer orphelinerait l'historique de tous les joueurs ayant déjà répondu.

### `POST /admin/questions/import` — saisie en masse

Le chemin rapide : écrire 50 questions dans un tableur, puis les envoyer d'un coup.

```json
{ "level_id": 12,
  "questions": [
    { "question_text": "Q1 ?", "difficulty": "easy",
      "answers": [ { "answer_text": "A", "is_correct": true },
                   { "answer_text": "B", "is_correct": false } ] } ] }
```

Maximum 100 lignes. **Tout ou rien** : si une seule ligne est invalide, aucune n'est créée, et le message indique la ligne fautive. Le panel doit l'afficher tel quel.

### `DELETE /admin/questions/{id}`
Archivage. Une question archivée n'est plus tirée mais son historique reste intact.

### L'écran à construire

Un formulaire unique qui **reste ouvert après enregistrement** : énoncé, 4 réponses avec un bouton radio pour la bonne, difficulté, explication. Un bouton **« Enregistrer et suivante »** qui conserve le niveau et la catégorie sélectionnés.

Si saisir une question prend deux minutes, la production s'arrête vers la 200ᵉ. Si elle en prend vingt secondes, le jeu vit.

---

## 6. Catégories

`GET`, `POST`, `PUT`, `DELETE` sur `/admin/categories` (pas de `show`). La liste n'est pas paginée.

Champs : `name`, `icon`, `color`, `is_active`.

`DELETE` sur une catégorie **encore utilisée** ne supprime pas : elle est désactivée, et la réponse l'explique. Supprimer réellement mettrait à `null` la catégorie de toutes ses questions, ce qui détruirait silencieusement leur classement.

---

## 7. Modération des signalements

Les joueurs signalent les questions douteuses depuis l'app.

| Verbe | Route |
| --- | --- |
| GET | `/admin/reports` — filtres `status`, `reason`, `question_id` |
| GET | `/admin/reports/{id}` |
| POST | `/admin/reports/{id}/resolve` |

La liste met **toujours les `pending` en tête**, puis trie par date décroissante. C'est une file de travail.

```json
POST /admin/reports/{id}/resolve
{ "status": "fixed", "deactivate_question": true }
```

`status` : `reviewed`, `rejected` ou `fixed`.

`deactivate_question: true` sort immédiatement la question de la rotation. **À proposer par défaut quand la clé de réponse est fausse** : tant qu'elle tourne, elle coûte des parties à tous les joueurs.

Chaque signalement embarque la question complète avec ses réponses — l'admin doit pouvoir juger sans changer d'écran.

---

## 8. Joueurs (support)

| Verbe | Route |
| --- | --- |
| GET | `/admin/players` — `search` (nom, email, code ami), `role` |
| GET | `/admin/players/{id}` — profil + 20 dernières parties |
| PATCH | `/admin/players/{id}` |

```json
PATCH /admin/players/{id}
{ "lives": 5, "coins": 500, "gems": 10, "role": "admin" }
```

Pour débloquer un joueur coincé par un bug, ou lui rendre ce qu'une question cassée lui a coûté. Chaque modification est journalisée avec l'id de l'admin.

**Un admin ne peut pas se retirer ses propres droits** (422) : ce serait un aller sans retour, l'API n'offrant aucun moyen de se les redonner.

---

## 9. Invalidation du cache

La carte joueur est mise en cache **6 heures**. Toute écriture sur une région, un chapitre ou un niveau la purge automatiquement — sans ça, le back-office semblerait ne rien faire pendant le reste de la journée.

Écrire une **question** ne purge rien : les questions sont tirées à chaque démarrage de partie, elles ne sont pas dans la carte.

---

## 10. Priorités pour le panel React

Dans l'ordre de valeur, pas de facilité :

1. **Éditeur de question** en saisie rapide, formulaire persistant — c'est le produit
2. **Import tableur** — plus rapide encore, même quand l'éditeur est excellent
3. **File des signalements** — la qualité du contenu se maintient là
4. **Niveaux incomplets** (`incomplete_only=1`) — les niveaux cassés bloquent des joueurs réels
5. **Équilibrage** (`question-balance`) — le réglage fin de la difficulté
6. Structure régions/chapitres/niveaux — utilisée rarement, une fois la carte posée
7. Joueurs — outil de support ponctuel

---

## 11. Voir aussi

- [API V1](API_V1.md) — endpoints joueur
- [Intégration Flutter](FLUTTER_INTEGRATION.md) — client mobile
- [Sécurité](SECURITY.md)
