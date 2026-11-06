# Déploiement des emails d’authentification

Configurer la production dans `.env` sans commiter le mot de passe :

```dotenv
APP_URL=https://laravelquizz.studiobeyam.net
AUTH_LINK_URL=https://laravelquizz.studiobeyam.net
MAIL_MAILER=smtp
MAIL_HOST=<serveur SMTP indiqué par cPanel>
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=noreply@studiobeyam.net
MAIL_PASSWORD=<mot de passe de la boîte>
MAIL_FROM_ADDRESS=noreply@studiobeyam.net
MAIL_FROM_NAME="IvoirQuizz"
QUEUE_CONNECTION=database
```

Si cPanel indique le port 587, utiliser `MAIL_PORT=587` et `MAIL_ENCRYPTION=tls`.

Après déploiement :

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

`optimize` met la configuration en cache : après toute modification du `.env` (mot de passe compris), relancer `optimize:clear` puis `optimize`, sinon Laravel garde l’ancienne valeur.

Tester l’envoi SMTP, qui affiche l’erreur exacte en cas d’échec :

```bash
php artisan mail:test votre.adresse@exemple.com
```

Les emails de vérification et de réinitialisation partent juste après la réponse HTTP, sans passer par la file d’attente : ils ne dépendent pas du Cron. En cas d’échec, le message d’erreur SMTP est écrit dans `storage/logs/laravel.log` (« Échec d’envoi d’un email d’authentification »). Le Cron `schedule:run` (voir `docs/ADMIN.md`) reste nécessaire pour les notifications push.

Les liens Android nécessitent ensuite ces variables fournies par Google Play :

```dotenv
ANDROID_APP_PACKAGE=
ANDROID_SHA256_FINGERPRINTS=
```

Tester une inscription, un renvoi de vérification et un mot de passe oublié après avoir relancé le cache.
