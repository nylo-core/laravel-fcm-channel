<?php

namespace VeskoDigital\LaravelFCM;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Notification;
use VeskoDigital\LaravelFCM\Channels\FCMChannel;

class FcmAppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerMigrations();

        Notification::extend('fcm_channel', function ($app) {
            return new FCMChannel();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->offerPublishing();
        $this->registerCommands();
    }

     /**
     * Setup the resource publishing groups for FCM Laravel.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/stubs/FcmAppServiceProvider.stub' => app_path('Providers/FcmAppServiceProvider.php'),
            ], 'laravel-fcm-provider');

            $this->publishes([
                __DIR__.'/../config/laravelfcm.php' => config_path('laravelfcm.php'),
            ], 'laravel-fcm-config');
        }
    }

    /**
     * Register the FCM Laravel migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    /**
     * Register the FCM Laravel routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $middleware = config('laravelfcm.middleware', ['auth:sanctum']);
        array_push($middleware, \VeskoDigital\LaravelFCM\Http\Middleware\AppApiRequestMiddleware::class);

        Route::group([
            'namespace' => 'VeskoDigital\LaravelFCM\Http\Controllers', 
            'prefix' => config('laravelfcm.path', 'api/fcm/'),
            'domain' => config('laravelfcm.domain', null),
            'middleware' => $middleware,
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        });
    }

     /**
     * Register the FCM Laravel Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class
            ]);
        }
    }
}