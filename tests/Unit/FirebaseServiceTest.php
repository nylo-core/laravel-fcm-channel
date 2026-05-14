<?php

namespace Nylo\LaravelFCM\Test\Unit;

use Kreait\Firebase\Factory;
use Nylo\LaravelFCM\Services\FirebaseService;
use Nylo\LaravelFCM\Test\TestCase;
use RuntimeException;

class FirebaseServiceTest extends TestCase
{
    public function test_throws_exception_when_config_is_empty()
    {
        config(['firebase_service_account_json' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Firebase service account JSON is not configured');

        new FirebaseService;
    }

    public function test_throws_exception_when_config_is_invalid_json()
    {
        config(['firebase_service_account_json' => 'not valid json {']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Firebase service account JSON:');

        new FirebaseService;
    }

    public function test_initializes_factory_with_valid_config()
    {
        $validJson = json_encode([
            'type' => 'service_account',
            'project_id' => 'test-project',
            'private_key_id' => 'key123',
            'private_key' => "-----BEGIN RSA PRIVATE KEY-----\nMIIBOgIBAAJBALRiMLAHudeSA2ai2EVEH4vYIeJxpZ9Opq6EEsYXq6jJ0aLmacFq\nJXsK8V3E5L1IaYpqYzDpwQQGvwQJahCxYyECAwEAAQJAYPVf2bJGjk9tM2DBdC/7\n+1F4aQrY8Cj3PYO9JlhKLDOf0vMB5GdL7M5VXdl4sNU4LbzcAqPr5HYrdxLEXrO7\njQIhAN7ydT9yD2S/u4SUYiLFlfuMG/sNJPv9lY6LE0+f5pMDAiEA0GIsQbq7SQID\nF7AFWjcCIwuH3V7ekJjcECPNFtI43ckCIQCh7h9OT1aQnbkc0Xkhl2N8SXdLcBzO\nXJuxjRufXjjBzQIgBPVWCT8KGAJ7eF8N9XMZsW8DWGef3Y0xWz0UMJEwEqkCIGz7\nCJxYf2Ll6KI1RLzFZCIMx2WA2lL1lStepPo+LxGP\n-----END RSA PRIVATE KEY-----\n",
            'client_email' => 'test@test-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]);

        config(['firebase_service_account_json' => $validJson]);

        $service = new FirebaseService;

        $this->assertInstanceOf(Factory::class, $service->getFactory());
    }
}
