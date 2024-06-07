<?php

namespace Nylo\LaravelFCM\Traits;

use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;

trait HasFcmDevices
{
	/**
     * Get the fcm devices.
     */
    public function fcmDevices()
    {
        return $this->morphMany(FcmDevice::class, 'notifyable');
    }

    /**
     * Determines if the devices can be notified.
     *
     * @return bool
     */
    public function canSendNotification($notification) : bool
    {
    	return true;
    }

    /**
     * Send a FCM message.
     *
     * @param  \Nylo\LaravelFCM\Models\FcmMessage|array  $message
     * @return void

     */
    public function sendFcmMessage($message)
    {
        if (!($message instanceof FcmMessage)) {
            $message = FcmMessage::createFromArray($message);
        }
        ProcessFcmNotificationsJob::dispatch($message, $this);
    }
}
