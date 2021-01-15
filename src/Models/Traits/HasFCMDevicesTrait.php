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
}
