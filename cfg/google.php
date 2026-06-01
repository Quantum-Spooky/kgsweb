<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GOOGLE SERVICE ACCOUNT
    |--------------------------------------------------------------------------
    */

    'service_account_file' =>
        ROOT_PATH . 'cfg/google-service-account.json',

    /*
    |--------------------------------------------------------------------------
    | GOOGLE DRIVE
    |--------------------------------------------------------------------------
    */

    'drive' => [

        'public_root_folder_id' =>
            '1L2vOHZlPrDnvXrGVFeTZa2duilKv89IL',

        'ticker_folder_id' =>
            '1g4Xsq_Yxb_Mq0OpwPUPLwzjnJHRzDocX',

        'breakfast_menu_folder_id' =>
            '1wK2IziGzOx8XgeDm0lEJp36k4J0N5Nd8',

        'lunch_menu_folder_id' =>
            '1hJpKtrg2-8o3m2lTqXArvEDVzc-kgz7l',

        'monthly_calendar_folder_id' =>
            '1j26-htFn1QxdEpRg2eHCVBI34rrtfIwP',

        'academic_calendar_folder_id' =>
            '1Mxes5W5ZTrTOl0G1xfHEP2o-IInhZWaJ',

        'pto_feature_image_folder_id' =>
            '1M_gJ2tcV2z90bRtWe-c-yWtqpbsedAl1',
    ],

    /*
    |--------------------------------------------------------------------------
    | GOOGLE CALENDAR
    |--------------------------------------------------------------------------
    */

    'calendar' => [

        'default_calendar_id' =>
            'c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071@group.calendar.google.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | FACEBOOK
    |--------------------------------------------------------------------------
    */

    'facebook' => [

        'school_url' =>
            'https://www.facebook.com/kellcsd2',

        'pto_url' =>
            'https://www.facebook.com/groups/1162635645047071',
    ],
];