<?php

namespace Nylo\LaravelFCM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Nylo\LaravelFCM\Models\FcmMessage;

class FcmSendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;
    public $device;

    public $fcmCloudMessagingService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(FcmMessage $notification, $device)
    {
        $this->notification = $notification;
        $this->device = $device;
        $this->fcmCloudMessagingService = resolve('Nylo\LaravelFCM\Services\FcmCloudMessagingService');
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

        $this->fcmCloudMessagingService->sendMessage($this->notification, $this->device);
    }
}
