<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Mockery;
use Nylo\LaravelFCM\Contracts\FcmNotifiable;
use Nylo\LaravelFCM\Jobs\ProcessFcmNotificationsJob;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Services\FcmCloudMessagingService;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class ProcessFcmNotificationsJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->connection()->getSchemaBuilder()->create('process_job_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_skips_when_no_firebase_config()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Laravel FCM Channel: Firebase service account json is not set');

        config(['firebase_service_account_json' => null]);

        $message = (new FcmMessage)->title('Test');
        $job = new ProcessFcmNotificationsJob($message, new ProcessJobTestUser);
        $job->handle();

        $this->assertTrue(true); // Job completed without error
    }

    public function test_skips_and_warns_when_notifiable_does_not_implement_contract()
    {
        config(['firebase_service_account_json' => '{"type": "service_account"}']);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message): bool {
                return str_contains($message, 'does not implement')
                    && str_contains($message, FcmNotifiable::class);
            });

        // Uses HasFcmDevices but forgets `implements FcmNotifiable` — the upgrade footgun.
        $notifiable = new class
        {
            use HasFcmDevices;

            public int $id = 1;
        };

        $service = Mockery::mock(FcmCloudMessagingService::class);
        $service->shouldNotReceive('sendMessages');
        $this->app->instance(FcmCloudMessagingService::class, $service);

        $job = new ProcessFcmNotificationsJob((new FcmMessage)->title('Test'), $notifiable);
        $job->handle();

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_no_devices()
    {
        config(['firebase_service_account_json' => '{"type": "service_account"}']);

        $user = ProcessJobTestUser::create(['name' => 'No Devices']);

        // No devices created -> the job returns before resolving the service.
        $service = Mockery::mock(FcmCloudMessagingService::class);
        $service->shouldNotReceive('sendMessages');
        $this->app->instance(FcmCloudMessagingService::class, $service);

        $job = new ProcessFcmNotificationsJob((new FcmMessage)->title('Test'), $user);
        $job->handle();

        $this->addToAssertionCount(1);
    }

    public function test_sends_when_notifiable_implements_contract_and_has_active_device()
    {
        config(['firebase_service_account_json' => '{"type": "service_account"}']);

        $user = ProcessJobTestUser::create(['name' => 'Has Device']);

        FcmDevice::create([
            'uuid' => 'pj-1',
            'notifyable_id' => $user->id,
            'notifyable_type' => ProcessJobTestUser::class,
            'fcm_token' => 'token-pj-1',
            'is_active' => true,
        ]);

        $message = (new FcmMessage)->title('Test');

        $service = Mockery::mock(FcmCloudMessagingService::class);
        $service->shouldReceive('sendMessages')
            ->once()
            ->withArgs(function (FcmMessage $sent, $devices) use ($message): bool {
                return $sent === $message && $devices->count() === 1;
            });
        $this->app->instance(FcmCloudMessagingService::class, $service);

        $job = new ProcessFcmNotificationsJob($message, $user);
        $job->handle();

        $this->addToAssertionCount(1);
    }

    public function test_accepts_array_notification()
    {
        $messageArray = [
            'title' => 'Test Title',
            'body' => 'Test Body',
        ];

        $job = new ProcessFcmNotificationsJob($messageArray, new ProcessJobTestUser);

        $this->assertInstanceOf(ProcessFcmNotificationsJob::class, $job);
        $this->assertInstanceOf(FcmMessage::class, $job->notification);
    }

    public function test_accepts_fcm_message_notification()
    {
        $message = (new FcmMessage)->title('Test Title')->body('Test Body');
        $job = new ProcessFcmNotificationsJob($message, new ProcessJobTestUser);

        $this->assertInstanceOf(ProcessFcmNotificationsJob::class, $job);
        $this->assertSame($message, $job->notification);
    }
}

class ProcessJobTestUser extends Model implements FcmNotifiable
{
    use HasFcmDevices;

    protected $table = 'process_job_users';

    protected $fillable = ['name'];
}
