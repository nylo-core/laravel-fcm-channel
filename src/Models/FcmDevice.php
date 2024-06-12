<?php

namespace Nylo\LaravelFCM\Models;

use Illuminate\Database\Eloquent\Model;
use Nylo\LaravelFCM\Jobs\FcmSendNotificationJob;

/**
 * Class FcmDevice
 *
 * @package Nylo\LaravelFCM\Models
 */
class FcmDevice extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fcm_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'display_name',
        'model',
        'platform',
        'version',
        'notifyable_id',
        'notifyable_type',
        'fcm_token',
        'is_active'
    ];

    /**
     * Scope models with fcm push token.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPushToken($query)
    {
        return $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
    }

    /**
     * Scope models which are active (not trashed).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Get the parent notifyable model.
     */
    public function notifyable()
    {
        return $this->morphTo();
    }

    /**
     * Send a message to this model.
     *
     * @param  \Nylo\LaravelFCM\Models\FcmMessage|array  $message
     * @return void
     */
    public function sendFcmMessage($message)
    {
        if (!($message instanceof FcmMessage)) {
            $message = FcmMessage::createFromArray($message);
        }
        FcmSendNotificationJob::dispatch($message, $this);
    }
}
