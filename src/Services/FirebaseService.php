<?php

namespace Nylo\LaravelFCM\Services;

use JsonException;
use Kreait\Firebase\Factory;
use RuntimeException;

/**
 * Class FirebaseService
 */
class FirebaseService
{
    protected Factory $factory;

    public function __construct()
    {
        $config = config('firebase_service_account_json');

        if (empty($config)) {
            throw new RuntimeException('Firebase service account JSON is not configured');
        }

        try {
            $credentials = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Invalid Firebase service account JSON: '.$e->getMessage());
        }

        $this->factory = (new Factory)->withServiceAccount($credentials);
    }

    /**
     * Get the factory.
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }
}
