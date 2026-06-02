<?php

namespace Nylo\LaravelFCM\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;

trait HasFcmDevices
{
    /**
     * Get the fcm devices.
     *
     * @return MorphMany<FcmDevice, $this>
     */
    public function fcmDevices(): MorphMany
    {
        return $this->morphMany(FcmDevice::class, 'notifyable');
    }

    /**
     * Determines if the devices can be notified.
     */
    public function canSendNotification($notification): bool
    {
        return true;
    }

    /**
     * Send a FCM message.
     *
     * @param  FcmMessage|array  $message
     * @return void
     */
    public function sendFcmMessage($message)
    {
        if (! ($message instanceof FcmMessage)) {
            $message = FcmMessage::createFromArray($message);
        }
        ProcessFcmNotificationsJob::dispatch($message, $this);
    }
}
