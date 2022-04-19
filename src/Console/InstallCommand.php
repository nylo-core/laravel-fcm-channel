<?php

namespace VeskoDigital\LaravelFCM\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use VeskoDigital\LaravelFCM\Console\Traits\DetectsApplicationNamespace;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelfcm:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Laravel FCM resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Laravel FCM Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-fcm-provider']);

        $this->comment('Publishing Laravel FCM Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-fcm-config']);

        $this->registerFcmAppServiceProvider();

        $this->info('Laravel FCM scaffolding installed successfully.');

        $arrTablesMissing = [];
        if (!Schema::hasTable('fcm_user_devices')) {
            $arrTablesMissing[] = 'fcm_user_devices';
        }

        if (!Schema::hasTable('fcm_api_app_requests')) {
            $arrTablesMissing[] = 'fcm_api_app_requests';
        }
        
        if (!empty($arrTablesMissing)) {
            $this->comment('You are missing the tables ' . implode(",", $arrTablesMissing) . ' for Laravel FCM to work...');

            if ($this->confirm('Would you also like to run the migration now too?')) {
                $this->comment('Running Laravel FCM migration...');
                $this->call('migrate', ['--path' => 'vendor/veskodigital/laravel-fcm-channel/src/database/migrations']);

                $this->info("Laravel FCM is installed 🎉");
            }
            return;
        }
    }

    /**
     * Register the service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerFcmAppServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->getAppNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\FcmAppServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\FcmAppServiceProvider::class,".PHP_EOL,
            $appConfig
        ));

        file_put_contents(app_path('Providers/FcmAppServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/FcmAppServiceProvider.php'))
        ));
    }
}
