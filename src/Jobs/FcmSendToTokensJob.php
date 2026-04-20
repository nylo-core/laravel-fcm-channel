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
use Nylo\LaravelFCM\Services\FcmCloudMessagingService;

class FcmSendToTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public FcmMessage $notification;

    public array $tokens;

    /**
     * Create a new job instance.
     */
    public function __construct(FcmMessage|array $notification, array $tokens)
    {
        $this->notification = $notification instanceof FcmMessage
            ? $notification
            : FcmMessage::createFromArray($notification);

        $this->tokens = array_values(array_unique(array_filter(
            $tokens,
            fn ($t) => is_string($t) && $t !== ''
        )));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty(config('firebase_service_account_json'))) {
            Log::error('[FcmSendToTokensJob] Job: Firebase service account json is not set');

            return;
        }

        if (empty($this->tokens)) {
            return;
        }

        $fcmCloudMessagingService = resolve(FcmCloudMessagingService::class);

        try {
            $fcmCloudMessagingService->sendToTokens($this->notification, $this->tokens);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     */
    public function failed($exception): void
    {
        Log::error('[FcmSendToTokensJob] Job failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
