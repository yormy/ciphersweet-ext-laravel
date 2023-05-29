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
    | ie:
    | 'models' => [
    |     App/Models/User::class,
    |     App/Models/Invoices::class
    | ]
    |
    */
    'models' => [
        Member::class,
        Admin::class
    ]

];
