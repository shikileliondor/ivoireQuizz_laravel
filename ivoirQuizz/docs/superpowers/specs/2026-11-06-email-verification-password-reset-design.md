# Vérification d’adresse et réinitialisation du mot de passe

## Intention

Permettre aux joueurs IvoirQuizz de créer immédiatement leur compte et d’utiliser l’application sans attendre une vérification, tout en leur proposant de confirmer leur adresse et de récupérer leur accès par email. Les réponses publiques ne doivent jamais révéler si une adresse est associée à un compte.

## Objectifs

- Conserver l’inscription immédiate et le jeton Sanctum déjà renvoyé à la création du compte.
- Envoyer automatiquement un email de vérification après une inscription par email, sans bloquer la réponse API si l’envoi est différé.
- Ouvrir l’application mobile depuis les boutons des emails au moyen de liens HTTPS universels.
- Permettre le renvoi d’un email de vérification avec un message identique pour une adresse connue ou inconnue.
- Conserver le parcours « Mot de passe oublié ? » existant et ouvrir dans l’application l’écran permettant de choisir le nouveau mot de passe.
- Invalider les jetons de connexion existants après une réinitialisation réussie.
- Utiliser `noreply@studiobeyam.net` comme expéditeur SMTP de production.

## Non-objectifs

- La vérification de l’adresse ne conditionne ni la connexion ni l’accès aux fonctionnalités de jeu.
- Cette évolution ne fournit pas le code Flutter absent de ce dépôt ; elle définit le contrat que l’application mobile devra implémenter.
- Aucun secret SMTP ne sera enregistré dans Git, les logs ou les réponses API.
- Les comptes Google dont l’adresse est déjà déclarée vérifiée par Google ne recevront pas inutilement ce parcours.

## Contraintes et sécurité

- L’origine HTTPS canonique est `https://laravelquizz.studiobeyam.net`.
- Le lien de vérification sera signé par Laravel, limité dans le temps et utilisable de manière idempotente.
- Le lien de réinitialisation continuera d’utiliser le broker Laravel et son jeton à durée limitée.
- Les liens email seront des URL HTTPS du domaine configuré, jamais des schémas personnalisés contenant directement des secrets.
- Android App Links associera les chemins d’authentification à l’application publiée. Le domaine devra exposer `/.well-known/assetlinks.json` avec les identifiants réels de l’application.
- Si l’application n’est pas installée, la même URL affichera une page web minimale et neutre plutôt qu’une erreur brute.
- Les endpoints de renvoi et de mot de passe oublié seront limités en fréquence et répondront toujours avec un message générique, que l’adresse existe ou non.
- Les emails seront normalisés en minuscules comme dans le service d’authentification existant.

## Approches considérées

### 1. Liens HTTPS universels — retenue

Les emails pointent vers des URL HTTPS de confiance, ouvertes par IvoirQuizz lorsque l’association mobile Android est installée. Cette approche offre une validation du domaine et une page de repli. Elle demande une configuration côté domaine et côté application Android.

### 2. Schéma personnalisé `ivoirquizz://`

Plus simple à déclarer, mais moins sûr contre l’interception, sans repli fiable lorsque l’application n’est pas installée. Cette approche a été écartée.

### 3. Confirmation entièrement web

Simple côté mobile, mais contraire au choix de faire aboutir l’utilisateur directement dans l’application. Cette approche a été écartée comme parcours principal ; seule une page de repli minimale est conservée.

## Conception Laravel

### Modèle et état exposé

`User` implémentera le contrat Laravel de vérification d’email. La colonne `email_verified_at` existe déjà. `UserResource` expose déjà cette date ; l’application pourra donc afficher l’état « vérifiée » ou « à vérifier » sans bloquer l’utilisateur.

### Inscription

Après la transaction de création réussie, Laravel déclenchera l’envoi de la notification de vérification. L’utilisateur et son jeton restent renvoyés immédiatement avec HTTP 201. L’échec temporaire de livraison ne doit pas annuler le compte ; un renvoi restera possible.

### API de vérification

- `POST /api/v1/auth/email/verification-notification` reçoit une adresse, applique le limiteur et retourne toujours un message neutre. Si un compte non vérifié existe, une notification est mise en file.
- Le lien signé HTTPS ouvre l’application sur un chemin tel que `/auth/verify-email` avec les paramètres signés nécessaires.
- L’application transmet ces paramètres à un endpoint Laravel de confirmation. Laravel vérifie la signature, l’identité et l’expiration, renseigne `email_verified_at`, puis renvoie un succès idempotent.
- Une adresse déjà vérifiée aboutit au même état final positif et ne donne pas d’information supplémentaire à un appelant anonyme.

### Mot de passe oublié

Les endpoints existants sont conservés :

- `POST /api/v1/auth/forgot-password` répond déjà par un message neutre ;
- `POST /api/v1/auth/reset-password` valide le jeton et le nouveau mot de passe, puis révoque les jetons Sanctum existants.

La notification existante sera modifiée pour produire le lien universel HTTPS convenu. L’application ouvrira l’écran « Nouveau mot de passe », conservera le jeton et l’adresse reçus, puis appellera l’endpoint de réinitialisation.

### Emails et file d’attente

Deux notifications françaises et cohérentes seront utilisées : « Vérifier mon adresse » et « Réinitialiser mon mot de passe ». Elles seront mises en file. La production devra donc avoir un worker actif ; si l’hébergement ne permet pas de processus permanent, une tâche cron lancera régulièrement le traitement de la file.

La configuration de production attendue est de la forme suivante, avec les valeurs exactes fournies par l’hébergeur :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=<serveur SMTP cPanel>
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=noreply@studiobeyam.net
MAIL_PASSWORD=<secret uniquement sur le serveur>
MAIL_FROM_ADDRESS=noreply@studiobeyam.net
MAIL_FROM_NAME="IvoirQuizz"
APP_URL=https://laravelquizz.studiobeyam.net
AUTH_LINK_URL=https://laravelquizz.studiobeyam.net
```

Le port et le chiffrement seront ajustés aux paramètres « Connect Devices » de cPanel si l’hébergeur impose plutôt le port 587/TLS.

## Contrat de l’application mobile

- Déclarer le domaine HTTPS comme Android App Link.
- Router `/auth/verify-email` vers un écran de confirmation qui appelle l’API puis affiche succès, expiration ou possibilité de renvoi.
- Router `/auth/reset-password` vers un formulaire contenant mot de passe et confirmation.
- Ajouter « Mot de passe oublié ? » à l’écran de connexion et un écran de saisie d’adresse affichant toujours le message neutre renvoyé par l’API.
- Après inscription, afficher discrètement que l’email a été envoyé, sans empêcher l’accès à l’application.
- Actualiser le profil après confirmation afin que `email_verified_at` soit reflété localement.

Les identifiants Android (package et empreinte SHA-256) devront être fournis depuis le dépôt mobile ou Google Play Console avant de finaliser `/.well-known/assetlinks.json`.

## Tests et critères d’acceptation

- Une inscription crée le compte, renvoie immédiatement un jeton utilisable et programme exactement une notification de vérification.
- Un compte non vérifié peut se connecter et utiliser les routes protégées ordinaires.
- Un lien de vérification valide renseigne `email_verified_at`; un second clic reste sans danger.
- Une signature modifiée ou expirée est refusée sans modifier le compte.
- Le renvoi de vérification produit la même réponse pour une adresse connue, inconnue ou déjà vérifiée.
- Le mot de passe oublié produit la même réponse pour une adresse connue ou inconnue.
- Un lien de réinitialisation valide permet de choisir le nouveau mot de passe et révoque les anciens jetons.
- Les liens générés utilisent HTTPS et les chemins universels configurés.
- Les notifications utilisent l’expéditeur IvoirQuizz configuré et aucun secret n’est commité.
- Les tests existants d’authentification et de mot de passe continuent de réussir.

## Déploiement

1. Déployer le code Laravel et exécuter les migrations normales avec `php artisan migrate --force` ; aucune réinitialisation de base n’est nécessaire.
2. Renseigner le SMTP dans le `.env` de production, vider le cache de configuration puis tester l’envoi vers une adresse contrôlée.
3. Publier les deux fichiers d’association `/.well-known` après obtention des identifiants mobiles.
4. Déployer la version mobile qui comprend les routes universelles.
5. Activer ou vérifier le worker de file, puis surveiller les échecs de notifications.

## Décisions laissées au constructeur

- Noms exacts des classes de contrôleur/service, tant que les responsabilités restent séparées et testables.
- Durée précise du lien de vérification, avec une valeur proposée de 60 minutes et une configuration centralisée.
- Présentation visuelle des écrans mobiles, à aligner sur le design existant de l’application.
