<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FCM SERVER TOKEN
    |--------------------------------------------------------------------------
    |
    | FCM server token from Firebase 
    | This can be found in project > settings > cloud messaging
    |
    */
    'fcm_server_key' => '',


     /*
    |--------------------------------------------------------------------------
    | LaraApp Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where LaraApp will be accessible from.
    | Note that the URI will not affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('LARAVEL_FCM_PATH', 'fcm'),


    /*
    |--------------------------------------------------------------------------
    | LaraApp Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each LaraApp route. If you 
    | want to add your own middleware to this list, you can attached them below.
    |
    */

    'middleware' => ['auth:sanctum'],
];