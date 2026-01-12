<?php

namespace Nylo\LaravelFCM\Test;

use Nylo\LaravelFCM\FcmAppServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            FcmAppServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.name', 'Test App');
    }
}
