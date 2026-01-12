<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Jobs\FcmSendNotificationJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Test\TestCase;

class FcmSendNotificationJobTest extends TestCase
{
    public function test_skips_when_no_firebase_config()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Laravel FCM Channel: Firebase service account json is not set');

        config(['firebase_service_account_json' => null]);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'test-token',
            'is_active' => true,
        ]);

        $message = (new FcmMessage)->title('Test');
        $job = new FcmSendNotificationJob($message, $device);
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_skips_when_device_empty()
    {
        config(['firebase_service_account_json' => '{"type": "service_account"}']);

        $message = (new FcmMessage)->title('Test');
        $job = new FcmSendNotificationJob($message, null);
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_is_queueable()
    {
        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'test-token',
            'is_active' => true,
        ]);

        $message = (new FcmMessage)->title('Test');
        $job = new FcmSendNotificationJob($message, $device);

        $this->assertInstanceOf(FcmSendNotificationJob::class, $job);
        $this->assertSame($message, $job->notification);
        $this->assertSame($device->id, $job->device->id);
    }
}
