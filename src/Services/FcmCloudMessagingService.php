<?php

namespace Nylo\LaravelFCM\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;
use Nylo\LaravelFCM\Enums\PriorityLevel;
use Nylo\LaravelFCM\Events\FcmMessageFailed;
use Nylo\LaravelFCM\Models\FcmDevice;
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
     * @param  Collection<int, FcmDevice>  $appDevices
     * @return void
     */
    public function sendMessages(FcmMessage $notificationMessage, $appDevices)
    {
        $messaging = $this->getFactory()->createMessaging();
        $message = $this->buildCloudMessage($notificationMessage);

        $fcmTokens = array_values(array_filter(
            $appDevices->pluck('fcm_token')->all(),
            fn ($token) => is_string($token) && $token !== ''
        ));
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
     * Send a message to an arbitrary list of FCM tokens.
     *
     * Tokens may belong to any number of notifiables, or come from outside
     * the `fcm_devices` table. Tokens are de-duplicated and chunked into
     * batches of 500 (Firebase's multicast limit).
     */
    /**
     * @param  array<int, mixed>  $tokens
     */
    public function sendToTokens(FcmMessage $notificationMessage, array $tokens): void
    {
        $tokens = array_values(array_unique(array_filter($tokens, fn ($t) => is_string($t) && $t !== '')));

        if (empty($tokens)) {
            return;
        }

        $messaging = $this->getFactory()->createMessaging();
        $message = $this->buildCloudMessage($notificationMessage);

        foreach (array_chunk($tokens, 500) as $chunk) {
            $report = $messaging->sendMulticast($message, $chunk);

            $tokensToDeactivate = array_merge(
                $report->unknownTokens(),
                $report->invalidTokens()
            );

            $deactivatedCount = $this->deactivateInvalidTokens($tokensToDeactivate);

            $this->handleFailures($report);

            Log::info('FCM multicast sent', [
                'success_count' => $report->successes()->count(),
                'failure_count' => $report->failures()->count(),
                'tokens_deactivated' => $deactivatedCount,
            ]);
        }
    }

    /**
     * Send a message to app device
     *
     * @return void
     */
    public function sendMessage(FcmMessage $notificationMessage, FcmDevice $appDevice)
    {
        $token = $appDevice->fcm_token;

        if (! is_string($token) || $token === '') {
            return;
        }

        $messaging = $this->getFactory()->createMessaging();
        $message = $this->buildCloudMessage($notificationMessage);

        $report = $messaging->sendMulticast($message, [$token]);

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

        $title = $firebaseMessageArray['title'] ?? null;
        if (! is_string($title) || $title === '') {
            $appName = config('app.name');
            $title = is_string($appName) && $appName !== '' ? $appName : null;
        }

        $body = $firebaseMessageArray['body'] ?? null;
        $image = $firebaseMessageArray['image'] ?? null;

        $notification = Notification::create(
            $title,
            is_string($body) && $body !== '' ? $body : null,
            is_string($image) && $image !== '' ? $image : null,
        );

        $message = CloudMessage::new()->withNotification($notification);

        $apnsConfig = ApnsConfig::new();
        $androidConfig = AndroidConfig::new();

        $badge = $firebaseMessageArray['badge'] ?? null;
        if (is_int($badge) && $badge !== 0) {
            $apnsConfig = $apnsConfig->withBadge($badge);
        }

        $sound = $firebaseMessageArray['sound'] ?? null;
        if (is_string($sound) && $sound !== '') {
            $apnsConfig = $apnsConfig->withSound($sound);
            $androidConfig = $androidConfig->withSound($sound);
        } elseif (empty($firebaseMessageArray['withoutDefaultSound'])) {
            $message = $message->withDefaultSounds();
        }

        $message = $message
            ->withApnsConfig($apnsConfig)
            ->withAndroidConfig($androidConfig);

        $priority = $firebaseMessageArray['priority'] ?? null;
        if (is_string($priority) && $priority !== '') {
            $message = match (PriorityLevel::tryFrom($priority)) {
                PriorityLevel::HIGHEST => $message->withHighestPossiblePriority(),
                PriorityLevel::LOWEST => $message->withLowestPossiblePriority(),
                default => $message,
            };
        }

        $data = $firebaseMessageArray['data'] ?? null;
        if (is_array($data) && $data !== []) {
            $message = $message->withData($this->normalizeData($data));
        }

        return $message;
    }

    /**
     * Normalize an arbitrary data payload into the string key/value map FCM requires.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<non-empty-string, string>
     */
    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;
            if ($key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null || $value instanceof \Stringable) {
                // scalars, null and Stringable all cast cleanly, matching Kreait's own (string) handling.
                $normalized[$key] = (string) $value;
            } else {
                $normalized[$key] = json_encode($value) ?: '';
            }
        }

        return $normalized;
    }

    /**
     * Handle failures from a multicast report.
     */
    private function handleFailures(MulticastSendReport $report): void
    {
        if (! $report->hasFailures()) {
            return;
        }

        foreach ($report->failures()->getItems() as $failure) {
            $error = $failure->error();

            Log::warning('FCM send failure', [
                'token' => $failure->target()->value(),
                'error' => $error?->getMessage(),
            ]);

            event(new FcmMessageFailed(
                $failure->target()->value(),
                $error?->getMessage() ?? ''
            ));
        }
    }

    /**
     * Deactivate invalid or unregistered FCM tokens.
     *
     * @param  array<int, string>  $tokens
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
