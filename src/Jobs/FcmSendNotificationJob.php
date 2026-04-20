<?php

namespace Nylo\LaravelFCM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;

class FcmSendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public FcmMessage $notification;

    public ?FcmDevice $device;

    /**
     * Create a new job instance.
     */
    public function __construct(FcmMessage $notification, ?FcmDevice $device)
    {
        $this->notification = $notification;
        $this->device = $device;
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

        if (empty($this->device)) {
            return;
        }

        if (empty($this->device->fcm_token)) {
            Log::warning('Laravel FCM Channel: Device has no FCM token', [
                'device_id' => $this->device->id ?? null,
            ]);

            return;
        }

        $fcmCloudMessagingService = resolve('Nylo\LaravelFCM\Services\FcmCloudMessagingService');
        $fcmCloudMessagingService->sendMessage($this->notification, $this->device);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed($exception)
    {
        \Log::error('[FcmSendNotificationJob] Job failed: '.$exception->getMessage());
    }
}
