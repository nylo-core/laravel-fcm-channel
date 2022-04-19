<?php

namespace VeskoDigital\LaravelFCM\Channels;

use Illuminate\Notifications\Notification;
use VeskoDigital\LaravelFCM\FcmCloud\Client;
use VeskoDigital\LaravelFCM\FcmCloud\Message;
use VeskoDigital\LaravelFCM\FcmCloud\Recipient\Device;
use VeskoDigital\LaravelFCM\FcmCloud\FCMNotification;
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

        // Chunk and send messages to recipients
        $fcmDevices->chunk(1000, function ($devices) use ($client, $payload) {

            // add recipients to notfication message
            $message = new Message();

            $priority = 'high';
            if (!empty($payload['priority'])) {
                $priority = $payload['priority'];
            }
            $message->setPriority($priority);

            foreach ($devices as $device) {
                $message->addRecipient(new Device($device->push_token));
            }

            // notfication payload
            $notification = new FCMNotification($payload['title'], $payload['body']);
            
            $sound = (!empty($payload['sound']) ? $payload['sound'] : 'default');
            $notification->setSound($sound);
            
            if (!empty($payload['badge'])) {
                $notification->setBadge($payload['badge']);
            }
            
            if (!empty($payload['android_channel_id'])) {
                $notification->setAndroidChannelId($payload['android_channel_id']);
            }

            if (!empty($payload['click_action'])) {
                $notification->setClickAction($payload['click_action']);
            }

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
        });
    }
}
