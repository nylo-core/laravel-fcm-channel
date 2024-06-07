<?php

namespace Nylo\LaravelFCM\Console\Traits;

use Illuminate\Container\Container;

trait DetectsApplicationNamespace
{
    /**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }
}
