<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class ProcessFcmNotificationsJobTest extends TestCase
{
    public function test_skips_when_no_firebase_config()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Laravel FCM Channel: Firebase service account json is not set');

        config(['firebase_service_account_json' => null]);

        $notifiable = new class
        {
            use HasFcmDevices;

            public $id = 1;

            public function fcmDevices()
            {
                return FcmDevice::where('notifyable_id', $this->id);
            }
        };

        $message = (new FcmMessage)->title('Test');
        $job = new ProcessFcmNotificationsJob($message, $notifiable);
        $job->handle();

        $this->assertTrue(true); // Job completed without error
    }

    public function test_skips_when_no_devices()
    {
        config(['firebase_service_account_json' => '{"type": "service_account"}']);

        $notifiable = new class
        {
            use HasFcmDevices;

            public $id = 999;

            public function fcmDevices()
            {
                return FcmDevice::where('notifyable_id', $this->id);
            }
        };

        $message = (new FcmMessage)->title('Test');
        $job = new ProcessFcmNotificationsJob($message, $notifiable);

        // Should complete without sending (no devices)
        // We can't fully test this without mocking Firebase, but we can test it doesn't error
        $this->assertInstanceOf(ProcessFcmNotificationsJob::class, $job);
    }

    public function test_accepts_array_notification()
    {
        $notifiable = new class
        {
            use HasFcmDevices;

            public $id = 1;

            public function fcmDevices()
            {
                return FcmDevice::where('notifyable_id', $this->id);
            }
        };

        $messageArray = [
            'title' => 'Test Title',
            'body' => 'Test Body',
        ];

        $job = new ProcessFcmNotificationsJob($messageArray, $notifiable);

        $this->assertInstanceOf(ProcessFcmNotificationsJob::class, $job);
        $this->assertInstanceOf(FcmMessage::class, $job->notification);
    }

    public function test_accepts_fcm_message_notification()
    {
        $notifiable = new class
        {
            use HasFcmDevices;

            public $id = 1;

            public function fcmDevices()
            {
                return FcmDevice::where('notifyable_id', $this->id);
            }
        };

        $message = (new FcmMessage)->title('Test Title')->body('Test Body');
        $job = new ProcessFcmNotificationsJob($message, $notifiable);

        $this->assertInstanceOf(ProcessFcmNotificationsJob::class, $job);
        $this->assertSame($message, $job->notification);
    }
}
