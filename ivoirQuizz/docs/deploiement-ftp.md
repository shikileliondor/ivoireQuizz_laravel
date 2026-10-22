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

Une installation ou migration initiale nécessite un accès SSH/terminal chez l’hébergeur pour créer la configuration, générer la clé et exécuter les migrations. FTP seul ne peut pas accomplir ces opérations.

## 2. Créer les secrets GitHub

Dans **Settings → Secrets and variables → Actions**, ajoutez ces *repository secrets* :

| Nom | Valeur |
| --- | --- |
| `FTP_HOST` | Nom d’hôte FTPS, sans préfixe `ftp://` |
| `FTP_USERNAME` | Nom du compte FTP |
| `FTP_PASSWORD` | Mot de passe FTP |

Ajoutez `FTP_SERVER_DIR` comme *repository variable* avec le chemin **absolu depuis la racine du compte FTP**. Exemple : `/ivoirquizz/`. Le workflow vérifie que ce chemin commence par `/` avant d’envoyer des fichiers.

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

Depuis un terminal SSH dans le dossier privé de l’application, lors de la première installation :

```sh
cp .env.example .env
php artisan key:generate
```

Éditez ensuite `.env` avec les valeurs de production ci-dessus. À chaque déploiement, si SSH est disponible, exécutez :

```sh
php artisan migrate --force
php artisan optimize
php artisan storage:link
```

Donnez à l’utilisateur PHP du site les droits d’écriture sur `storage/` et `bootstrap/cache/`. Si SSH n’est pas inclus dans l’offre, faites effectuer l’initialisation/migration par le terminal de l’hébergeur ou son support. N’ouvrez pas une route HTTP publique pour exécuter arbitrairement les migrations.

## Limites du déploiement FTP

FTP synchronise des fichiers un par un et ne fournit pas de bascule atomique entre releases; un déploiement peut donc être brièvement incomplet. L’action conserve son fichier d’état de synchronisation sur le serveur, et le nettoyage destructif est désactivé pour protéger `.env`, les fichiers téléversés et les fichiers gérés sur l’hébergement. N’utilisez pas ce compte FTP pour publier un artefact non fiable.
