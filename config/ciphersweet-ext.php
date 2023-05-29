<?php

use Mexion\BedrockUsers\Models\Member;
use Mexion\BedrockUsers\Models\Admin;

return [
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
