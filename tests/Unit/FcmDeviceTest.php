<?php

namespace Nylo\LaravelFCM\Test\Unit;

use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Test\TestCase;

class FcmDeviceTest extends TestCase
{
    public function test_scope_active_filters_inactive_devices()
    {
        FcmDevice::create([
            'uuid' => 'active-device-uuid',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'token-1',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'inactive-device-uuid',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'token-2',
            'is_active' => false,
        ]);

        $activeDevices = FcmDevice::active()->get();

        $this->assertCount(1, $activeDevices);
        $this->assertEquals('active-device-uuid', $activeDevices->first()->uuid);
    }

    public function test_scope_with_push_token_filters_empty_tokens()
    {
        FcmDevice::create([
            'uuid' => 'device-with-token',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'valid-token',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-without-token',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => null,
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-empty-token',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => '',
            'is_active' => true,
        ]);

        $devicesWithToken = FcmDevice::withPushToken()->get();

        $this->assertCount(1, $devicesWithToken);
        $this->assertEquals('device-with-token', $devicesWithToken->first()->uuid);
    }

    public function test_fillable_attributes()
    {
        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'display_name' => 'iPhone 15',
            'model' => 'iPhone15,2',
            'platform' => 'ios',
            'version' => '17.0',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'test-token',
            'is_active' => true,
        ]);

        $this->assertEquals('test-uuid', $device->uuid);
        $this->assertEquals('iPhone 15', $device->display_name);
        $this->assertEquals('iPhone15,2', $device->model);
        $this->assertEquals('ios', $device->platform);
        $this->assertEquals('17.0', $device->version);
        $this->assertEquals(1, $device->notifyable_id);
        $this->assertEquals('App\Models\User', $device->notifyable_type);
        $this->assertEquals('test-token', $device->fcm_token);
        $this->assertTrue((bool) $device->is_active);
    }

    public function test_combined_scopes()
    {
        FcmDevice::create([
            'uuid' => 'active-with-token',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'token',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'inactive-with-token',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'token-2',
            'is_active' => false,
        ]);

        FcmDevice::create([
            'uuid' => 'active-without-token',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => null,
            'is_active' => true,
        ]);

        $devices = FcmDevice::active()->withPushToken()->get();

        $this->assertCount(1, $devices);
        $this->assertEquals('active-with-token', $devices->first()->uuid);
    }
}
