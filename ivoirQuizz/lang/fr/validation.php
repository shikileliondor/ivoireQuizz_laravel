<?php

// Messages utilisés par le back-office (locale forcée à « fr » sur /admin). L'API reste en anglais.
return [
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, chiffres, tirets et underscores.',
    'array' => 'Le champ :attribute doit être une liste.',
    'between' => [
        'numeric' => 'Le champ :attribute doit être entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'distinct' => 'Deux :attribute sont identiques.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'enum' => 'La valeur choisie pour :attribute est invalide.',
    'exists' => 'La valeur choisie pour :attribute est introuvable.',
    'gte' => [
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur choisie pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'max' => [
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'mimes' => 'Le fichier :attribute doit être de type : :values.',
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'not_in' => 'La valeur de :attribute est invalide.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être un texte.',
    'unique' => 'Ce :attribute est déjà utilisé.',
    'uploaded' => 'Le fichier :attribute n’a pas pu être envoyé.',

    'custom' => [
        'color' => ['regex' => 'La couleur doit être au format #RRGGBB.'],
    ],

    'attributes' => [
        'email' => 'email',
        'password' => 'mot de passe',
        'description' => 'description',
        'image' => 'image',
        'status' => 'statut',
        'type' => 'type',
        'from' => 'date de début',
        'to' => 'date de fin',
    ],
];
