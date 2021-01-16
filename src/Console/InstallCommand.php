<?php

namespace WooSignal\LaravelFCM\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use WooSignal\LaravelFCM\Console\Traits\DetectsApplicationNamespace;
use Illuminate\Support\Facades\Schema;
use Hash;

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
    protected $description = 'Install all of the Laravel FCM Notify resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Laravel FCM Notify Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-fcm-provider']);

        $this->comment('Publishing Laravel FCM Notify Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-fcm-config']);

        $this->registerFcmAppServiceProvider();

        $this->info('Laravel FCM Notify scaffolding installed successfully.');

        $arrTablesMissing = [];
        if (!Schema::hasTable('user_devices')) {
            $arrTablesMissing[] = 'user_devices';
        }

        if (count($arrTablesMissing) > 0) {
            $this->comment('You are missing the tables ' . implode(",", $arrTablesMissing) . ' for Laravel FCM Notify to work...');

            if ($this->confirm('Would you also like to run the migration now too?')) {
                $this->comment('Running Laravel FCM Notify migration...');
                $this->call('migrate', ['--path' => 'vendor/woosignal/laravel-fcm-notify/src/database/migrations']);

                $this->info("Laravel FCM Notify is installed 🎉");
            }
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
