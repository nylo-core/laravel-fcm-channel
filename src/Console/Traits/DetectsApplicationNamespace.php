<?php

namespace Nylo\LaravelFCM\Console\Traits;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

trait DetectsApplicationNamespace
{
    /**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        $app = Container::getInstance();

        if ($app instanceof Application) {
            return $app->getNamespace();
        }

        return 'App\\';
    }
}
