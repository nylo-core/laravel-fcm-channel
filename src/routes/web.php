<?php

use Illuminate\Support\Facades\Route;
use VeskoDigital\LaravelFCM\Http\Controllers\LaravelFcmController;

Route::put('device', [LaravelFcmController::class, 'update'])->name('laravel_notify_fcm.update');

Route::post('device', [LaravelFcmController::class, 'store'])->name('laravel_notify_fcm.store');