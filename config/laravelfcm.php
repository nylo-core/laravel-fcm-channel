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
    'fcm_server_key' => env('LARAVEL_FCM_SERVER_KEY'),


     /*
    |--------------------------------------------------------------------------
    | PATH
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Laravel FCM will be accessible from.
    | Note that the URI will not affect the paths of its internal API that aren't exposed to users.
    |
    */
    'path' => env('LARAVEL_FCM_PATH', 'api/fcm/'),

    /*
    |--------------------------------------------------------------------------
    | MODEL FOR NOTIFICATIONS
    |--------------------------------------------------------------------------
    |
    | Here you can set the default notification model for your application.
    | The below will use the user model for the polymorphic relationship.
    |
    */
    'default_notifyable_model' => 'App\Models\User',

    /*
    |--------------------------------------------------------------------------
    | AUTH MIDDLEWARE
    |--------------------------------------------------------------------------
    |
    | When using the Flutter package, the application will try to authenticate
    | via Laravel sanctum
    |
    */
    'middleware' => ['auth:sanctum'],


    /*
    |--------------------------------------------------------------------------
    | DOMAIN
    |--------------------------------------------------------------------------
    |
    | Here you can define the domain URL that the API can be reached from.
    | E.g. 'api.mysite.com', by default this is your current domain URL.
    |
    */
    'domain' => null,
];