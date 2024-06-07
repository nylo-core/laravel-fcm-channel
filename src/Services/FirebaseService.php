<?php

namespace Nylo\LaravelFCM\Services;

use Kreait\Firebase\Factory;

/**
 * Class FirebaseService
 *
 * @property Factory $factory
 *
 * @package Nylo\LaravelFCM\Services
 */
 class FirebaseService
 {
    public function __construct()
    {
        $this->factory = (new Factory)->withServiceAccount(json_decode(config('firebase_service_account_json'), true));
    }

    /**
     * Get the factory
     *
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }
 }
