<?php

return [
    'url' => rtrim((string) env('AUTH_LINK_URL', env('APP_URL', 'https://laravelquizz.studiobeyam.net')), '/'),
    'verification_expire' => (int) env('EMAIL_VERIFICATION_EXPIRE', 60),
    'android' => [
        'package' => env('ANDROID_APP_PACKAGE'),
        'sha256_cert_fingerprints' => array_values(array_filter(array_map('trim', explode(',', (string) env('ANDROID_SHA256_FINGERPRINTS', ''))))),
    ],
];
