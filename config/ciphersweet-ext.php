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
    | Additional models
    |--------------------------------------------------------------------------
    |
    | Additional models that are not part of the main app, ie in the vendor section
    |
    */
    'models' => [
        Member::class,
        Admin::class
    ]

];
