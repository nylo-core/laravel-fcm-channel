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

    public function test_does_not_dispatch_when_notification_has_no_to_fcm_method()
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

        // Base notification without a toFcm() method.
        $notification = new class extends Notification {};

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        Queue::assertNotPushed(ProcessFcmNotificationsJob::class);
    }

    public function test_does_not_dispatch_when_to_fcm_returns_unsupported_payload()
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
                return null; // neither FcmMessage nor array
            }
        };

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        Queue::assertNotPushed(ProcessFcmNotificationsJob::class);
    }

    public function test_dispatches_when_to_fcm_returns_array_payload()
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
                return ['title' => 'Hi', 'body' => 'There'];
            }
        };

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        Queue::assertPushed(ProcessFcmNotificationsJob::class, function (ProcessFcmNotificationsJob $job) {
            return $job->notification instanceof FcmMessage
                && $job->notification->toArray()['title'] === 'Hi';
        });
    }

    public function test_dispatches_when_notifiable_has_no_can_send_notification_method()
    {
        Queue::fake();

        // Plain object: no canSendNotification() — the guard must not fatal.
        $notifiable = new class {};

        $notification = new class extends Notification
        {
            public function toFcm($notifiable)
            {
                return (new FcmMessage)->title('Test');
            }
        };

        $channel = new FCMChannel;
        $channel->send($notifiable, $notification);

        Queue::assertPushed(ProcessFcmNotificationsJob::class);
    }
}
