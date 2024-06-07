<?php

namespace Nylo\LaravelFCM\Channels;

use Illuminate\Notifications\Notification;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;

class FCMChannel
{
    /**
     * Send a notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $payload = $notification->toFcm($notifiable);

        if ($notifiable->canSendNotification(get_class($notification)) === false) {
            return;
        }

        ProcessFcmNotificationsJob::dispatch($payload, $notifiable);
    }
}
