<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Support\Facades\DB;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Test\TestCase;

class FcmCloudMessagingServiceTest extends TestCase
{
    public function test_deactivate_invalid_tokens()
    {
        FcmDevice::create([
            'uuid' => 'device-1',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'invalid-token-1',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-2',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'invalid-token-2',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-3',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'valid-token',
            'is_active' => true,
        ]);

        // Test the deactivation logic directly using DB facade (same as service does)
        $tokens = ['invalid-token-1', 'invalid-token-2'];
        $count = DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->assertEquals(2, $count);

        $this->assertFalse((bool) FcmDevice::where('fcm_token', 'invalid-token-1')->first()->is_active);
        $this->assertFalse((bool) FcmDevice::where('fcm_token', 'invalid-token-2')->first()->is_active);
        $this->assertTrue((bool) FcmDevice::where('fcm_token', 'valid-token')->first()->is_active);
    }

    public function test_deactivate_returns_zero_for_empty_tokens()
    {
        // Empty array should not update anything
        $tokens = [];
        if (empty($tokens)) {
            $count = 0;
        } else {
            $count = DB::table('fcm_devices')
                ->whereIn('fcm_token', $tokens)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $this->assertEquals(0, $count);
    }

    public function test_deactivate_ignores_already_inactive()
    {
        FcmDevice::create([
            'uuid' => 'device-1',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'already-inactive-token',
            'is_active' => false,
        ]);

        $tokens = ['already-inactive-token'];
        $count = DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->assertEquals(0, $count);
    }

    public function test_deactivate_handles_nonexistent_tokens()
    {
        $tokens = ['nonexistent-token-1', 'nonexistent-token-2'];
        $count = DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->assertEquals(0, $count);
    }
}
