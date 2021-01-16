<?php

namespace WooSignal\LaravelFCM;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Gate;

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

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
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
     * Setup the resource publishing groups for LaraApp.
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
     * Register the LaraApp migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    /**
     * Register the LaraApp routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'namespace' => 'WooSignal\LaravelFCM\Http\Controllers', 
            'prefix' => config('laravelfcm.path', 'laravel-fcm-notify'),
            'domain' => config('laravelfcm.domain', null),
            'middleware' => config('laravelfcm.middleware', ['auth:sanctum']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        });
    }

     /**
     * Register the LaraApp Artisan commands.
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