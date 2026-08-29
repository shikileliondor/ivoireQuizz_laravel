# IvoireQuiz — panel d'administration

Back-office React qui consomme l'API `/api/v1/admin` de Laravel.
Documentation de l'API : [`docs/ADMIN_API.md`](../docs/ADMIN_API.md).

## Démarrer

```bash
# 1. L'API Laravel, depuis la racine du projet
php artisan serve            # http://127.0.0.1:8000

# 2. Le panel
cd frontend
cp .env.example .env         # ajuste VITE_API_TARGET si besoin
npm install
npm run dev                  # http://localhost:5173
```

Le serveur de dev **proxifie `/api` vers Laravel**, donc le panel reste same-origin
et il n'y a aucun CORS à configurer côté backend.

### Compte administrateur

```bash
php artisan db:seed --class=AdminSeeder
```

Crée `admin@ivoirequiz.com` / `Admin@2025!` — **à changer avant toute mise en ligne.**

Un compte sans `role = 'admin'` peut se connecter à l'API mais le panel le
refusera : l'accès est vérifié en appelant `/admin/dashboard`, puisque `/me`
n'expose pas le rôle.

## Scripts

| Commande | Effet |
| --- | --- |
| `npm run dev` | Serveur de développement avec HMR |
| `npm run build` | Build de production dans `dist/` |
| `npm run preview` | Sert le build local |
| `npm run lint` | Oxlint |

## Organisation

```
src/
  api/
    client.js       Wrapper fetch : jeton, enveloppe { success, data }, ApiError
    endpoints.js    Une fonction par route admin
  auth/             Contexte de session + garde d'accès admin
  hooks/            useResource (fetch + annulation), useDebounced
  lib/constants.js  Libellés métier et formatage FR
  pages/            Un fichier par écran
  ui/               Layout, composants partagés, toasts
  index.css         Design system (tokens, thème clair/sombre)
```

### Le client API en une phrase

Chaque écran reçoit soit une donnée typée, soit une `ApiError` qui porte le
`message` du serveur. Les 422 de l'API sont écrits en français pour un humain :
ils s'affichent tels quels, sans reformulation.

Un 401 n'importe où purge le jeton et renvoie à l'écran de connexion, une fois,
au niveau du shell.

## Les écrans, par ordre d'importance

1. **Saisir une question** (`/questions/nouvelle`) — le formulaire reste ouvert
   après enregistrement, garde le niveau et la catégorie, et se raccourcit en
   `Ctrl + Entrée`. C'est l'écran qui décide si le jeu atteint 1000 questions.
2. **Import en masse** (`/questions/import`) — coller depuis un tableur, avec
   aperçu et validation ligne par ligne avant envoi.
3. **Signalements** (`/signalements`) — file de modération, `pending` en tête.
4. **Niveaux** (`/niveaux`) — le filtre « injouables seulement » liste les
   niveaux qui tirent plus de questions qu'ils n'en possèdent, et bloquent donc
   les joueurs.
5. **Tableau de bord** — santé du contenu, équilibrage par taux de réussite,
   entonnoir d'abandon.
6. Régions / chapitres / catégories — posés une fois, retouchés rarement.
7. **Joueurs** — support ponctuel.

## Déploiement

`npm run build` produit `dist/`. Sers-le derrière le même domaine que l'API
(ou en `public/admin` côté Laravel) pour que `/api` reste relatif. Le routeur
étant en mode history, le serveur doit renvoyer `index.html` sur toute route
inconnue.
