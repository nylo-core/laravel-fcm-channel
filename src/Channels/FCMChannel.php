<?php

namespace WooSignal\LaravelFCM\Channels;

use Illuminate\Notifications\Notification;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification as FCMNotification;
use Exception;
use Illuminate\Support\Facades\Log;

class FCMChannel
{
    /**
     * Send the given notification.
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

        $fcmDevices = $notifiable->fcmDevices()->active()->withPushToken();
        if ($fcmDevices->count() == 0) {
            return;
        }

        $client = new Client();
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $client->setApiKey(config('laravelfcm.fcm_server_key', ''));

        $message = new Message();
        $message->setPriority($payload['priority']);

        foreach ($fcmDevices->get() as $device) {
            $message->addRecipient(new Device($device->push_token));
        }

        $notification = new FCMNotification($payload['title'], $payload['body']);
        $notification->setSound((!empty($payload['sound']) ? $payload['sound'] : 'default'));
        $notification->setBadge((!empty($payload['badge']) ? $payload['badge'] : 1));
        $notification->setAndroidChannelId((!empty($payload['android_channel_id']) ? $payload['android_channel_id'] : ""));

        if (!empty($payload['click_action'])) {
            $notification->setClickAction($payload['click_action']);
        }

        $messageData = [];
        if (isset($payload['data'])) {
            $messageData['data'] = $payload['data'];
        }
        
        $message->setNotification($notification)
        ->setData($messageData);

        try {
            $client->send($message);
        } catch (Exception $e) {
            Log::error($e->getMessage());   
        }
    }
}