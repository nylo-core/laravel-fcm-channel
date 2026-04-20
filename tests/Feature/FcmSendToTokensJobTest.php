<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Jobs\FcmSendToTokensJob;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Test\TestCase;

class FcmSendToTokensJobTest extends TestCase
{
    public function test_skips_when_no_firebase_config()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('[FcmSendToTokensJob] Job: Firebase service account json is not set');

        config(['firebase_service_account_json' => null]);

        $job = new FcmSendToTokensJob((new FcmMessage)->title('Test'), ['token-1']);
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_skips_when_tokens_are_empty()
    {
        config(['firebase_service_account_json' => '{"type": "service_account"}']);

        $job = new FcmSendToTokensJob((new FcmMessage)->title('Test'), []);
        $job->handle();

        $this->assertSame([], $job->tokens);
    }

    public function test_dedupes_and_filters_tokens_on_construction()
    {
        $job = new FcmSendToTokensJob(
            (new FcmMessage)->title('Test'),
            ['a', 'b', 'a', '', null, 'c', 'b']
        );

        $this->assertSame(['a', 'b', 'c'], $job->tokens);
    }

    public function test_accepts_array_notification()
    {
        $job = new FcmSendToTokensJob(
            ['title' => 'Test Title', 'body' => 'Test Body'],
            ['token-1']
        );

        $this->assertInstanceOf(FcmMessage::class, $job->notification);
        $this->assertSame(['token-1'], $job->tokens);
    }

    public function test_accepts_fcm_message_notification()
    {
        $message = (new FcmMessage)->title('Test')->body('Body');
        $job = new FcmSendToTokensJob($message, ['token-1']);

        $this->assertSame($message, $job->notification);
    }
}
