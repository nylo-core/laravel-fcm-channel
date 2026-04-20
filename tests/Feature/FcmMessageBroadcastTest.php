<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Nylo\LaravelFCM\Jobs\FcmSendToTokensJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class FcmMessageBroadcastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->connection()->getSchemaBuilder()->create('broadcast_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_send_to_tokens_dispatches_job_with_deduped_tokens()
    {
        Queue::fake();

        $message = (new FcmMessage)->title('Hello')->body('Broadcast');

        $message->sendToTokens(['a', 'b', 'a', '', 'c']);

        Queue::assertPushed(FcmSendToTokensJob::class, function (FcmSendToTokensJob $job) {
            return $job->tokens === ['a', 'b', 'c'];
        });
    }

    public function test_send_to_tokens_with_empty_array_still_dispatches()
    {
        Queue::fake();

        (new FcmMessage)->title('Hello')->sendToTokens([]);

        Queue::assertPushed(FcmSendToTokensJob::class, function (FcmSendToTokensJob $job) {
            return $job->tokens === [];
        });
    }

    public function test_send_to_notifiables_pools_active_tokens_across_users()
    {
        Queue::fake();

        $userA = BroadcastTestUser::create(['name' => 'A']);
        $userB = BroadcastTestUser::create(['name' => 'B']);

        FcmDevice::create([
            'uuid' => 'a-1',
            'notifyable_id' => $userA->id,
            'notifyable_type' => BroadcastTestUser::class,
            'fcm_token' => 'token-a1',
            'is_active' => true,
        ]);
        FcmDevice::create([
            'uuid' => 'a-2',
            'notifyable_id' => $userA->id,
            'notifyable_type' => BroadcastTestUser::class,
            'fcm_token' => 'token-a2',
            'is_active' => false, // inactive, should be excluded
        ]);
        FcmDevice::create([
            'uuid' => 'a-3',
            'notifyable_id' => $userA->id,
            'notifyable_type' => BroadcastTestUser::class,
            'fcm_token' => '', // empty token, should be excluded
            'is_active' => true,
        ]);
        FcmDevice::create([
            'uuid' => 'b-1',
            'notifyable_id' => $userB->id,
            'notifyable_type' => BroadcastTestUser::class,
            'fcm_token' => 'token-b1',
            'is_active' => true,
        ]);

        (new FcmMessage)->title('Hello')->sendToNotifiables([$userA, $userB]);

        Queue::assertPushed(FcmSendToTokensJob::class, function (FcmSendToTokensJob $job) {
            sort($job->tokens);

            return $job->tokens === ['token-a1', 'token-b1'];
        });
    }

    public function test_send_to_notifiables_skips_items_without_fcm_devices_method()
    {
        Queue::fake();

        $stranger = new \stdClass; // no fcmDevices() method
        $user = BroadcastTestUser::create(['name' => 'U']);
        FcmDevice::create([
            'uuid' => 'u-1',
            'notifyable_id' => $user->id,
            'notifyable_type' => BroadcastTestUser::class,
            'fcm_token' => 'token-u1',
            'is_active' => true,
        ]);

        (new FcmMessage)->title('Hello')->sendToNotifiables([$stranger, $user]);

        Queue::assertPushed(FcmSendToTokensJob::class, function (FcmSendToTokensJob $job) {
            return $job->tokens === ['token-u1'];
        });
    }
}

class BroadcastTestUser extends Model
{
    use HasFcmDevices;

    protected $table = 'broadcast_users';

    protected $fillable = ['name'];
}
