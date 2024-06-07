<?php

namespace Nylo\LaravelFCM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Log;
use Nylo\LaravelFCM\Models\FcmMessage;

class ProcessFcmNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

    public $notifiable;

    public $fcmCloudMessagingService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification, $notifiable)
    {
        if (!($notification instanceof FcmMessage)) {
            $this->notification = FcmMessage::createFromArray($notification);
        } else {
            $this->notification = $notification;
        }
        $this->notifiable = $notifiable;
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

        $fcmDevices = $this->notifiable->fcmDevices()->active()->withPushToken();

        if ($fcmDevices->count() == 0) {
            return;
        }

        $fcmDevices->chunk(500, function($devices) {
            try {
                $this->fcmCloudMessagingService->sendMessages($this->notification, $devices);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        });
    }
}
