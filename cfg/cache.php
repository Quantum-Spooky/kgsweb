<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CACHE ROOT
    |--------------------------------------------------------------------------
    */

    'root' => ROOT_PATH . 'kgs-cache/',

    /*
    |--------------------------------------------------------------------------
    | GOOGLE CACHE
    |--------------------------------------------------------------------------
    */

    'google' => [

        'drive' =>
            ROOT_PATH . 'kgs-cache/google/drive/',

        'documents' =>
            ROOT_PATH . 'kgs-cache/google/documents/',

        'menus' =>
            ROOT_PATH . 'kgs-cache/google/menus/',

        'ticker' =>
            ROOT_PATH . 'kgs-cache/google/ticker/',

        'events' =>
            ROOT_PATH . 'kgs-cache/google/events/',

        'sheets' =>
            ROOT_PATH . 'kgs-cache/google/sheets/',

        'slides' =>
            ROOT_PATH . 'kgs-cache/google/slides/',
    ],
];