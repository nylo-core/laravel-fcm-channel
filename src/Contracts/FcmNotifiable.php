<?php

namespace Nylo\LaravelFCM\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

/**
 * Marks an Eloquent model as a target for FCM push notifications.
 *
 * Implement this alongside the {@see HasFcmDevices}
 * trait, which supplies the default morph-many implementation:
 *
 * ```php
 * class User extends Authenticatable implements FcmNotifiable
 * {
 *     use HasFcmDevices;
 * }
 * ```
 */
interface FcmNotifiable
{
    /**
     * Get the FCM devices that belong to the notifiable.
     *
     * @return MorphMany<FcmDevice, Model>
     */
    public function fcmDevices(): MorphMany;
}
