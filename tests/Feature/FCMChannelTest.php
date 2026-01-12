<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Queue;
use Nylo\LaravelFCM\Channels\FCMChannel;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class FCMChannelTest extends TestCase
{
    public function test_dispatches_job_when_sending()
    {
        Queue::fake();

        $notifiable = new class
        {
            use HasFcmDevices;

            public function fcmDevices()
            {
                return collect();
            }
        };

        $notification = new class extends Notification
        {
            public function toFcm($notifiable)
            {
                return (new FcmMessage)->title('Test')->body('Body');
            }
        };

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        Queue::assertPushed(ProcessFcmNotificationsJob::class);
    }

    public function test_respects_can_send_notification_false()
    {
        Queue::fake();

        $notifiable = new class
        {
            use HasFcmDevices;

            public function canSendNotification($notification): bool
            {
                return false;
            }

            public function fcmDevices()
            {
                return collect();
            }
        };

        $notification = new class extends Notification
        {
            public function toFcm($notifiable)
            {
                return (new FcmMessage)->title('Test');
            }
        };

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        Queue::assertNotPushed(ProcessFcmNotificationsJob::class);
    }

    public function test_sends_even_when_to_fcm_returns_empty_message()
    {
        Queue::fake();

        $notifiable = new class
        {
            use HasFcmDevices;

            public function fcmDevices()
            {
                return collect();
            }
        };

        $notification = new class extends Notification
        {
            public function toFcm($notifiable)
            {
                return new FcmMessage; // Empty message
            }
        };

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        // Channel dispatches job regardless of message content
        Queue::assertPushed(ProcessFcmNotificationsJob::class);
    }
}
