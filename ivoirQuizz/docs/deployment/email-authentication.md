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

Pour une file `database`, ajouter une tâche cron chaque minute :

```cron
* * * * * cd /chemin/absolu/du/projet && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

Les liens Android nécessitent ensuite ces variables fournies par Google Play :

```dotenv
ANDROID_APP_PACKAGE=
ANDROID_SHA256_FINGERPRINTS=
```

Tester une inscription, un renvoi de vérification et un mot de passe oublié après avoir relancé le cache.
