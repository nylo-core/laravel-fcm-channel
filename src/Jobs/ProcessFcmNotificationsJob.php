<?php

namespace Nylo\LaravelFCM\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Models\FcmMessage;

class ProcessFcmNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public FcmMessage $notification;

    public mixed $notifiable;

    /**
     * Create a new job instance.
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
     *
     * @return void
     */
    public function handle()
    {
        if (empty(config('firebase_service_account_json'))) {
            Log::error('Laravel FCM Channel: Firebase service account json is not set');

            return;
        }

        $fcmDevices = $this->notifiable->fcmDevices()->active()->withPushToken();

        if ($fcmDevices->count() === 0) {
            return;
        }

        $fcmCloudMessagingService = resolve('Nylo\LaravelFCM\Services\FcmCloudMessagingService');

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
     * @return void
     */
    public function failed($exception)
    {
        \Log::error('[ProcessFcmNotificationsJob] Job failed: '.$exception->getMessage());
    }
}
