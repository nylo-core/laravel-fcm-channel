<?php

namespace Nylo\LaravelFCM\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Contracts\FcmNotifiable;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Services\FcmCloudMessagingService;

class ProcessFcmNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public FcmMessage $notification;

    public mixed $notifiable;

    /**
     * Create a new job instance.
     *
     * @param  FcmMessage|array<string, mixed>  $notification
     * @param  mixed  $notifiable  The entity to notify; expected to implement FcmNotifiable.
     */
    public function __construct(FcmMessage|array $notification, mixed $notifiable)
    {
        if (! ($notification instanceof FcmMessage)) {
            $this->notification = FcmMessage::createFromArray($notification);
        } else {
            $this->notification = $notification;
        }
        $this->notifiable = $notifiable;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty(config('firebase_service_account_json'))) {
            Log::error('Laravel FCM Channel: Firebase service account json is not set');

            return;
        }

        $notifiable = $this->notifiable;

        if (! $notifiable instanceof FcmNotifiable) {
            Log::warning('Laravel FCM Channel: notifiable ['.get_debug_type($notifiable).'] does not implement '.FcmNotifiable::class.' and was skipped; no FCM notifications were sent. Implement the contract on your notifiable model (the HasFcmDevices trait already satisfies it).');

            return;
        }

        $fcmDevices = $notifiable->fcmDevices()
            ->active()
            ->withPushToken();

        if ($fcmDevices->count() === 0) {
            return;
        }

        $fcmCloudMessagingService = resolve(FcmCloudMessagingService::class);

        $fcmDevices->chunk(500, function ($devices) use ($fcmCloudMessagingService) {
            try {
                $fcmCloudMessagingService->sendMessages($this->notification, $devices);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        });
    }

    /**
     * Handle a job failure.
     *
     * @param  Exception  $exception
     */
    public function failed($exception): void
    {
        Log::error('[ProcessFcmNotificationsJob] Job failed: '.$exception->getMessage());
    }
}
