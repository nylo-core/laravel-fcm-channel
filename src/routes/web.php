<?php

use Illuminate\Support\Facades\Route;
use WooSignal\LaravelFCM\Http\Controllers\LaravelFcmController;

Route::group(
    [
        'prefix' => 'api',
        'middleware' => WooSignal\LaravelFCM\Http\Middleware\AppApiRequestMiddleware::class
    ], function() {
        Route::put('token', [LaravelFcmController::class, 'update'])->name('laravel_notify_fcm.token.update');
        Route::post('token', [LaravelFcmController::class, 'store'])->name('laravel_notify_fcm.token.store');
    }
);
