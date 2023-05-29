<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | The anonymizer is a critical function that destroys data
    | it should only be allowed to run on test data.
    | Specify the name of the environment where it is allowed
    | Example:
    |    'environments' => [
    |      'local',
    }      'test',
    |    ],
    */
    'environments' => [
        'local',
        'test',
    ],

];
