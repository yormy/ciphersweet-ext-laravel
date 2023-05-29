<?php

use Mexion\BedrockUsers\Models\Member;
use Mexion\BedrockUsers\Models\Admin;

return [
    /*
    |--------------------------------------------------------------------------
    | Ignore paths
    |--------------------------------------------------------------------------
    |
    | Paths to ignore searching for models
    | Example:
    |   'ignore' => [
    |       'App\Helpers',
    |       'App\Services'
    |   ],
    |
    */
    'ignore' => [
        'App\Helpers',
        'App\Services',
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryptable models
    |--------------------------------------------------------------------------
    |
    | List the models that you want to encrypt
    |
    */
    'models' => [
        Member::class,
        Admin::class
    ]

];
