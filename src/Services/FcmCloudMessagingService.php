<?php

namespace Nylo\LaravelFCM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Nylo\LaravelFCM\Events\FcmMessageFailed;
use Nylo\LaravelFCM\Models\FcmMessage;

/**
 * Class FcmCloudMessagingService
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
     *
     * @return void
     */
    public function sendMessages(FcmMessage $notificationMessage, $appDevices)
    {
        $messaging = $this->getFactory()->createMessaging();
        $message = $this->buildCloudMessage($notificationMessage);

        $fcmTokens = $appDevices->pluck('fcm_token')->toArray();
        $report = $messaging->sendMulticast($message, $fcmTokens);

        // Deactivate invalid/unregistered tokens
        $tokensToDeactivate = array_merge(
            $report->unknownTokens(),  // UNREGISTERED - user uninstalled app
            $report->invalidTokens()   // Invalid token format
        );

        $deactivatedCount = $this->deactivateInvalidTokens($tokensToDeactivate);

        $this->handleFailures($report);

        Log::info('FCM multicast sent', [
            'success_count' => $report->successes()->count(),
            'failure_count' => $report->failures()->count(),
            'tokens_deactivated' => $deactivatedCount,
        ]);
    }

    /**
     * Send a message to app device
     *
     *
     * @return void
     */
    public function sendMessage(FcmMessage $notificationMessage, $appDevice)
    {
        $messaging = $this->getFactory()->createMessaging();
        $message = $this->buildCloudMessage($notificationMessage);

        $report = $messaging->sendMulticast($message, [$appDevice->fcm_token]);

        // Deactivate if token is invalid/unregistered
        $tokensToDeactivate = array_merge(
            $report->unknownTokens(),
            $report->invalidTokens()
        );

        $this->deactivateInvalidTokens($tokensToDeactivate);
        $this->handleFailures($report);
    }

    /**
     * Build a CloudMessage from an FcmMessage.
     */
    private function buildCloudMessage(FcmMessage $notificationMessage): CloudMessage
    {
        $firebaseMessageArray = $notificationMessage->toArray();

        $notificationArray = [];
        $notificationArray['title'] = empty($firebaseMessageArray['title'])
            ? config('app.name')
            : $firebaseMessageArray['title'];

        if (! empty($firebaseMessageArray['body'])) {
            $notificationArray['body'] = $firebaseMessageArray['body'];
        }

        if (! empty($firebaseMessageArray['image'])) {
            $notificationArray['image'] = $firebaseMessageArray['image'];
        }

        $message = CloudMessage::new()->withNotification($notificationArray);

        $apnsConfig = ApnsConfig::new();
        $androidConfig = AndroidConfig::new();

        if (! empty($firebaseMessageArray['badge'])) {
            $apnsConfig = $apnsConfig->withBadge($firebaseMessageArray['badge']);
        }

        if (! empty($firebaseMessageArray['sound'])) {
            $apnsConfig = $apnsConfig->withSound($firebaseMessageArray['sound']);
            $androidConfig = $androidConfig->withSound($firebaseMessageArray['sound']);
        } elseif (empty($firebaseMessageArray['withoutDefaultSound'])) {
            $message = $message->withDefaultSounds();
        }

        $message = $message
            ->withApnsConfig($apnsConfig)
            ->withAndroidConfig($androidConfig);

        if (! empty($firebaseMessageArray['priority'])) {
            if ($firebaseMessageArray['priority'] === 'highest') {
                $message = $message->withHighestPossiblePriority();
            } elseif ($firebaseMessageArray['priority'] === 'lowest') {
                $message = $message->withLowestPossiblePriority();
            }
        }

        if (! empty($firebaseMessageArray['data'])) {
            $message = $message->withData($firebaseMessageArray['data']);
        }

        return $message;
    }

    /**
     * Handle failures from a multicast report.
     */
    private function handleFailures($report): void
    {
        if (! $report->hasFailures()) {
            return;
        }

        foreach ($report->failures()->getItems() as $failure) {
            Log::warning('FCM send failure', [
                'token' => $failure->target()->value(),
                'error' => $failure->error()?->getMessage(),
            ]);

            event(new FcmMessageFailed(
                $failure->target()->value(),
                $failure->error()->getMessage()
            ));
        }
    }

    /**
     * Deactivate invalid or unregistered FCM tokens.
     */
    public function deactivateInvalidTokens(array $tokens): int
    {
        if (empty($tokens)) {
            return 0;
        }

        return DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
