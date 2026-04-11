<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | This array contains the currencies supported by the application.
    |
    */
    'currencies' => [
        'TRY' => [
            'code' => 'TRY',
            'name' => 'Turkish Lira',
            'symbol' => '₺',
        ],
        'USD' => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ],
        'EUR' => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('DEFAULT_CURRENCY', 'TRY'),

    /*
    |--------------------------------------------------------------------------
    | AI Models
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'model' => env('AI_MODEL', 'gemini-1.5-pro'),
    ],
];
