<?php

return [
    // Le dépôt se fait HORS de l'application — ces informations sont
    // uniquement affichées au client pour qu'il envoie l'argent lui-même.
    'payment' => [
        'orange_money' => [
            'label' => 'Orange Money',
            'name' => env('ORANGE_MONEY_NAME', 'Mr. Shawarma'),
            'number' => env('ORANGE_MONEY_NUMBER', '+237 6 90 00 00 00'),
        ],
        'mtn_momo' => [
            'label' => 'MTN Mobile Money',
            'name' => env('MTN_MOMO_NAME', 'Mr. Shawarma'),
            'number' => env('MTN_MOMO_NUMBER', '+237 6 70 00 00 00'),
        ],
    ],
];
