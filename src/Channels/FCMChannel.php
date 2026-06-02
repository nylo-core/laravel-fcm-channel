<?php

namespace Nylo\LaravelFCM\Channels;

use Illuminate\Notifications\Notification;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmMessage;

class FCMChannel
{
    /**
     * Send a notification.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        if (! $payload instanceof FcmMessage) {
            if (! is_array($payload)) {
                return;
            }

            $message = [];
            foreach ($payload as $key => $value) {
                $message[(string) $key] = $value;
            }
            $payload = $message;
        }

        if (is_object($notifiable)
            && method_exists($notifiable, 'canSendNotification')
            && ! $notifiable->canSendNotification(get_class($notification))) {
            return;
        }

        ProcessFcmNotificationsJob::dispatch($payload, $notifiable);
    }
}
