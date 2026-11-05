<?php

/*
|--------------------------------------------------------------------------
| Site public (vitrine, pages légales, suppression de compte)
|--------------------------------------------------------------------------
|
| Ces informations apparaissent dans les mentions légales et la politique de
| confidentialité. Renseignez-les dans .env avant la publication : Google Play
| vérifie que la politique nomme l'éditeur et donne un contact joignable.
|
*/

return [
    // Personne ou société qui publie l'application sur Google Play.
    'publisher' => [
        'name' => env('SITE_PUBLISHER_NAME', env('APP_NAME', 'IvoirQuizz')),
        'legal_form' => env('SITE_PUBLISHER_LEGAL_FORM'),
        'registration' => env('SITE_PUBLISHER_REGISTRATION'),
        'address' => env('SITE_PUBLISHER_ADDRESS'),
        'director' => env('SITE_PUBLICATION_DIRECTOR'),
    ],

    'contact_email' => env('SITE_CONTACT_EMAIL'),

    'host' => [
        'name' => env('SITE_HOST_NAME', 'LWS (Ligne Web Services), SAS'),
        'address' => env('SITE_HOST_ADDRESS', '10 rue Penthièvre, 75008 Paris, France'),
        'website' => env('SITE_HOST_WEBSITE', 'https://www.lws.fr'),
    ],

    // Lien de la fiche Google Play ; tant qu'il est vide, le site affiche « Bientôt disponible ».
    'play_store_url' => env('SITE_PLAY_STORE_URL'),

    'minimum_age' => (int) env('SITE_MINIMUM_AGE', 13),

    // Date affichée en tête des pages légales. Mettez-la à jour à chaque modification de leur contenu.
    'legal_updated_at' => env('SITE_LEGAL_UPDATED_AT', '2026-11-05'),
];
