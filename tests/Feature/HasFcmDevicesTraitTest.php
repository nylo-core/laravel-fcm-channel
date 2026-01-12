<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class HasFcmDevicesTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create users table for testing
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_fcm_devices_relationship()
    {
        $user = TestUser::create(['name' => 'Test User']);

        FcmDevice::create([
            'uuid' => 'device-1',
            'notifyable_id' => $user->id,
            'notifyable_type' => TestUser::class,
            'fcm_token' => 'token-1',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-2',
            'notifyable_id' => $user->id,
            'notifyable_type' => TestUser::class,
            'fcm_token' => 'token-2',
            'is_active' => true,
        ]);

        $this->assertCount(2, $user->fcmDevices);
    }

    public function test_can_send_notification_returns_true_by_default()
    {
        $user = new TestUser;

        $this->assertTrue($user->canSendNotification('SomeNotification'));
    }

    public function test_send_fcm_message_dispatches_job()
    {
        Queue::fake();

        $user = TestUser::create(['name' => 'Test User']);
        $message = (new FcmMessage)->title('Test')->body('Body');

        $user->sendFcmMessage($message);

        Queue::assertPushed(ProcessFcmNotificationsJob::class);
    }

    public function test_send_fcm_message_accepts_array()
    {
        Queue::fake();

        $user = TestUser::create(['name' => 'Test User']);
        $messageArray = ['title' => 'Test', 'body' => 'Body'];

        $user->sendFcmMessage($messageArray);

        Queue::assertPushed(ProcessFcmNotificationsJob::class);
    }
}

class TestUser extends Model
{
    use HasFcmDevices;

    protected $table = 'users';

    protected $fillable = ['name'];
}
