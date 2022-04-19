<?php

namespace VeskoDigital\LaravelFCM\Models;

use VeskoDigital\LaravelFCM\FcmCloud\Client;
use Illuminate\Database\Eloquent\Model;
use VeskoDigital\LaravelFCM\FcmCloud\Message;
use VeskoDigital\LaravelFCM\FcmCloud\Recipient\Device;
use Exception, Log;
use VeskoDigital\LaravelFCM\FcmCloud\FCMNotification;

class FcmUserDevice extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fcm_user_devices';
    
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
        'push_token',
        'is_active'
    ];

    /**
     * Find all the api requests made for this device.
     */
    public function fcmDeviceAPIRequests()
    {
        return $this->hasMany(FcmDeviceAPIRequest::class);
    }

    /**
     * Scope models with fcm push token.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPushToken($query)
    {
        return $query->whereNotNull('push_token')->where('push_token', '!=', '');
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
     * Send a notification to this model.
     */
    public function send(FCMNotification $notification, $priority = 'high')
    {
        $pushToken = $this->push_token;
        if (empty($pushToken)) {
            Log::error("Failed to send notification. Push token is empty for " . get_class($this));
            return;
        }

        $client = new Client();
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $client->setApiKey(config('laravelfcm.fcm_server_key', ''));
        $message->addRecipient(new Device($pushToken));
        $message->setPriority($priority);

         // set notfication
         $notificationMessage = $message->setNotification($notification);

         if (!empty($payload['data'])) {
             $messageData = [
                 'data' => $payload['data']
             ];

             $notificationMessage->setData($messageData);
         }

         // send notfication
         try {
             $client->send($notificationMessage);
         } catch (Exception $e) {
             Log::error($e->getMessage());
         }
    }
}
