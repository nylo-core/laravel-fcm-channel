<?php

namespace WooSignal\LaravelFCM\Models\Traits;
use WooSignal\LaravelFCM\Models\UserDevice;

trait HasFCMDevicesTrait
{

	/**
     * Get the fcm devices.
     */
    public function fcmDevices()
    {
        return $this->morphMany(UserDevice::class, 'notifyable');
    }

    /**
     * Determines if the devices can be notified.
     *
     * @return bool
     */
    public function canSendNotification() : bool
    {
    	return true;
    }
}
