# Déploiement FTP de production

Le workflow GitHub Actions `.github/workflows/deploy-ftp.yml` prépare Laravel et ses assets Vite, puis synchronise `release/` vers l’hébergement en **FTPS explicite**. Il se lance sur chaque push vers `main`, ou manuellement depuis l’onglet Actions.

## 1. Préparer l’hébergement

Il faut un hébergement qui fournisse PHP 8.2 ou plus, Composer compatible avec `composer.lock`, MySQL ou MariaDB, HTTPS, et FTPS explicite sur le port 21. FTP sans chiffrement n’est pas configuré.

La racine web doit pointer vers le dossier `public/` de Laravel. La structure conseillée est :

```text
/home/COMPTE/ivoirquizz/       # code Laravel, hors racine web
/home/COMPTE/public_html/      # racine web, contenu du dossier Laravel public/
```

Si l’hébergeur impose `public_html`, transférez **le contenu de `release/public/`** dans `public_html`, et le reste de `release/` dans un dossier Laravel privé adjacent. Dans `public_html/index.php`, ajustez les chemins vers `vendor/autoload.php` et `bootstrap/app.php` si le dossier privé n’est pas le parent direct. Ne placez jamais `.env`, `vendor`, `storage` ou le code applicatif privé dans une racine web accessible.

Le workflow regroupe les dépendances Composer de production dans `vendor.zip` pour éviter l’envoi de milliers de petits fichiers. Il téléverse l’archive à la racine de l’application et n’envoie pas le dossier `vendor/` individuellement.

Une installation ou migration initiale nécessite le gestionnaire de fichiers LWS pour extraire cette archive, ainsi que pour créer la configuration `.env`, générer la clé et exécuter les migrations. FTP seul ne peut pas exécuter les commandes Artisan.

## 2. Créer les secrets GitHub

Dans **Settings → Secrets and variables → Actions**, ajoutez ces *repository secrets* :

| Nom | Valeur |
| --- | --- |
| `FTP_HOST` | Nom d’hôte FTPS, sans préfixe `ftp://` |
| `FTP_USERNAME` | Nom du compte FTP |
| `FTP_PASSWORD` | Mot de passe FTP |

Ajoutez `FTP_SERVER_DIR` comme *repository secret* avec le dossier cible relatif à la racine de votre compte FTP, par exemple `public_html/` ou `ivoirquizz/`. L’action FTP utilise les chemins relatifs depuis le dossier d’accueil FTP; le workflow ajoute automatiquement le `/` final si besoin.

Créez un compte FTP restreint au dossier privé du site et à sa racine publique, avec les seules permissions d’écriture nécessaires. Si l’hébergeur ne prend en charge que le FTPS implicite ou le FTP simple, cette configuration ne sera pas compatible.

## 3. Configurer `.env` sur le serveur

Créez le fichier `.env` sur le serveur par terminal ou gestionnaire de fichiers sécurisé. Il n’est jamais envoyé par GitHub Actions. Configurez au minimum :

```dotenv
APP_NAME=IvoirQuizz
APP_ENV=production
APP_KEY=base64:REMPLACER_PAR_LA_CLE_GENERÉE_SUR_LE_SERVEUR
APP_DEBUG=false
APP_URL=https://votre-domaine.example

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nom_base
DB_USERNAME=utilisateur_base
DB_PASSWORD=mot_de_passe_base

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

Utilisez les valeurs d’hôte, base, utilisateur et mot de passe données par l’hébergeur. Gardez la clé `APP_KEY` stable après l’installation. N’utilisez pas `APP_DEBUG=true` en production. `QUEUE_CONNECTION=sync` traite immédiatement les tâches en arrière-plan; configurez un worker séparément si vous avez besoin d’une file d’attente durable. `SESSION_DRIVER=file` évite de dépendre d’une table sessions préexistante.

## 4. Initialiser et terminer le déploiement

Dans le gestionnaire de fichiers LWS, ouvrez le dossier privé où se trouvent `artisan` et `bootstrap/`, puis extrayez `vendor.zip` **dans ce dossier**. L’archive contient déjà le dossier `vendor/`; après extraction, vérifiez que `vendor/autoload.php` existe à côté de `artisan`. Vous pouvez ensuite supprimer l’archive distante `vendor.zip` pour libérer de l’espace. Répétez l’extraction et remplacez le dossier `vendor/` lorsque les dépendances changent (notamment après une mise à jour de `composer.lock`).

Créez `.env` dans ce même dossier, en partant de `.env.example` si le gestionnaire LWS permet de copier/renommer le fichier, puis remplacez les valeurs par celles de production ci-dessus. Générez une clé avec le générateur de clé de l’hébergement s’il en propose un; sinon, créez une clé Laravel localement avec `php artisan key:generate --show` et copiez sa valeur dans `APP_KEY`. Conservez cette clé après l’installation.

Les migrations et les commandes Artisan de cache/lien storage ne sont pas exécutables par FTP seul. Utilisez un terminal dans le panneau LWS s’il est disponible, ou demandez à leur support de lancer `php artisan migrate --force`, `php artisan optimize` et `php artisan storage:link` dans le dossier privé. N’ouvrez pas une route HTTP publique pour lancer les migrations. Donnez à l’utilisateur PHP du site les droits d’écriture sur `storage/` et `bootstrap/cache/`.

## Limites du déploiement FTP

FTP synchronise des fichiers un par un et ne fournit pas de bascule atomique entre releases; un déploiement peut donc être brièvement incomplet. L’action conserve son fichier d’état de synchronisation sur le serveur, et le nettoyage destructif est désactivé pour protéger `.env`, les fichiers téléversés et les fichiers gérés sur l’hébergement. N’utilisez pas ce compte FTP pour publier un artefact non fiable.
