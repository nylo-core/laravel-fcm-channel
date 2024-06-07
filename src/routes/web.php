<?php

use Illuminate\Support\Facades\Route;
use Nylo\LaravelFCM\Http\Controllers\LaravelFcmController;

Route::put('device', [LaravelFcmController::class, 'update'])->name('laravel_notify_fcm.update');
