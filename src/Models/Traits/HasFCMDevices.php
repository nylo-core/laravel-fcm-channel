<?php

namespace VeskoDigital\LaravelFCM\Models\Traits;

use VeskoDigital\LaravelFCM\Models\FcmUserDevice;

trait HasFCMDevices
{
	/**
     * Get the fcm devices.
     */
    public function fcmDevices()
    {
        return $this->morphMany(FcmUserDevice::class, 'notifyable');
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
}
