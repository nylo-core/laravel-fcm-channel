<?php

namespace Nylo\LaravelFCM\Services;

use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\FcmOptions;
use Log;
use Nylo\LaravelFCM\Models\FcmMessage;

/**
 * Class FcmCloudMessagingService
 *
 * @package Nylo\LaravelFCM\Services
 */
class FcmCloudMessagingService extends FirebaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Send a message to app devices
     *
     * @param FcmMessage $notificationMessage
     * @param $appDevices
     *
     * @return void
     */
    public function sendMessages(FcmMessage $notificationMessage, $appDevices)
    {
        $messaging = $this->getFactory()->createMessaging();
        $firebaseMessageArray = $notificationMessage->toArray();

        $notificationArray = [];

        $notificationArray['title'] = empty($firebaseMessageArray['title']) ? config('app.name') : $firebaseMessageArray['title'];

        if (!empty($firebaseMessageArray['body'])) {
            $notificationArray['body'] = $firebaseMessageArray['body'];
        }

        if (!empty($firebaseMessageArray['image'])) {
            $notificationArray['image'] = $firebaseMessageArray['image'];
        }

        $message = CloudMessage::new();
        $message = $message->withNotification($notificationArray);

        $apnsConfig = ApnsConfig::new();
        $androidConfig = AndroidConfig::new();

        if (!empty($firebaseMessageArray['badge'])) {
            $apnsConfig = $apnsConfig->withBadge($firebaseMessageArray['badge']);
        }

        if (!empty($firebaseMessageArray['sound'])) {
            $apnsConfig = $apnsConfig->withSound($firebaseMessageArray['sound']);
            $androidConfig = $androidConfig->withSound($firebaseMessageArray['sound']);
        }

        $message = $message->withApnsConfig(
            $apnsConfig
        );

        $message = $message->withAndroidConfig(
            $androidConfig
        );

        if (!empty($firebaseMessageArray['priority'])) {
            if ($firebaseMessageArray['priority'] == 'highest') {
                $message = $message->withHighestPossiblePriority();
            }

            if ($firebaseMessageArray['priority'] == 'lowest') {
                $message = $message->withLowestPossiblePriority();
            }
        }

        if (!empty($firebaseMessageArray['data'])) {
            $message = $message->withData($firebaseMessageArray['data']);
        }

        if (empty($firebaseMessageArray['withoutDefaultSound'])) {
            $message = $message->withDefaultSounds();
        }

        $fcmTokens = $appDevices->pluck('fcm_token')->toArray();

        $report = $messaging->sendMulticast($message, $fcmTokens);

        if ($report->hasFailures()) {
            foreach ($report->failures()->getItems() as $failure) {
                Log::error('Laravel FCM Channel: ' . $failure->error()->getMessage());
            }
        }
    }

    /**
     * Send a message to app device
     *
     * @param FcmMessage $notificationMessage
     * @param $appDevice
     *
     * @return void
     */
    public function sendMessage(FcmMessage $notificationMessage, $appDevice)
    {
        $messaging = $this->getFactory()->createMessaging();
        $firebaseMessageArray = $notificationMessage->toArray();

        $notificationArray = [];

        $notificationArray['title'] = empty($firebaseMessageArray['title']) ? config('app.name') : $firebaseMessageArray['title'];

        if (!empty($firebaseMessageArray['body'])) {
            $notificationArray['body'] = $firebaseMessageArray['body'];
        }

        if (!empty($firebaseMessageArray['image'])) {
            $notificationArray['image'] = $firebaseMessageArray['image'];
        }

        $message = CloudMessage::new();
        $message = $message->withNotification($notificationArray);

        $apnsConfig = ApnsConfig::new();
        $androidConfig = AndroidConfig::new();

        if (!empty($firebaseMessageArray['badge'])) {
            $apnsConfig = $apnsConfig->withBadge($firebaseMessageArray['badge']);
        }

        if (!empty($firebaseMessageArray['sound'])) {
            $apnsConfig = $apnsConfig->withSound($firebaseMessageArray['sound']);
            $androidConfig = $androidConfig->withSound($firebaseMessageArray['sound']);
        }

        $message = $message->withApnsConfig(
            $apnsConfig
        );

        $message = $message->withAndroidConfig(
            $androidConfig
        );

        $message = $message->withDefaultSounds();
        if (!empty($firebaseMessageArray['priority'])) {
            if ($firebaseMessageArray['priority'] == 'highest') {
                $message = $message->withHighestPossiblePriority();
            }

            if ($firebaseMessageArray['priority'] == 'lowest') {
                $message = $message->withLowestPossiblePriority();
            }
        }

        if (!empty($firebaseMessageArray['data'])) {
            $message = $message->withData($firebaseMessageArray['data']);
        }

        $report = $messaging->sendMulticast($message, $appDevice->fcm_token);

        if ($report->hasFailures()) {
            foreach ($report->failures()->getItems() as $failure) {
                Log::error('Laravel FCM Channel: ' . $failure->error()->getMessage());
            }
        }
    }
}
