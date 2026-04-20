<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Http\Middleware\AppApiRequestMiddleware;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppApiRequestMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        config(['laravelfcm.default_notifyable_model' => MiddlewareTestUser::class]);
    }

    public function test_returns_403_when_user_not_authenticated()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('FCM middleware did not receive an authenticated user');

        $middleware = new AppApiRequestMiddleware();
        $request = Request::create('/test', 'GET');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_returns_400_when_x_dmeta_header_is_missing()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('FCM middleware received a malformed X-DMeta header');

        $user = MiddlewareTestUser::create(['name' => 'Test']);
        $middleware = new AppApiRequestMiddleware();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_returns_400_when_x_dmeta_header_is_empty_json()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('FCM middleware has empty X-DMETA data');

        $user = MiddlewareTestUser::create(['name' => 'Test']);
        $middleware = new AppApiRequestMiddleware();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-DMETA', '{}');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_creates_new_device_when_uuid_not_found()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);
        $middleware = new AppApiRequestMiddleware();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'new-device-uuid',
            'model' => 'iPhone 15',
            'display_name' => 'My iPhone',
            'platform' => 'ios',
            'version' => '17.0',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $response = $middleware->handle($request, fn ($r) => response(['device_id' => $r->get('device')->id]));

        $this->assertDatabaseHas('fcm_devices', [
            'uuid' => 'new-device-uuid',
            'notifyable_id' => $user->id,
            'platform' => 'ios',
        ]);
    }

    public function test_finds_existing_device_by_fcm_token()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $existingDevice = FcmDevice::create([
            'uuid' => 'existing-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => 'existing-token-123',
            'is_active' => true,
        ]);

        $middleware = new AppApiRequestMiddleware();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'different-uuid',
            'fcm_token' => 'existing-token-123',
            'model' => 'iPhone 15',
            'display_name' => 'My iPhone',
            'platform' => 'ios',
            'version' => '17.0',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, function ($r) use ($existingDevice) {
            $this->assertEquals($existingDevice->id, $r->get('device')->id);

            return response('ok');
        });
    }

    public function test_merges_fcm_token_from_request_body()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $middleware = new AppApiRequestMiddleware();
        $request = Request::create('/test', 'POST', ['fcm_token' => 'body-token-123']);
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'new-device-uuid',
            'model' => 'iPhone 15',
            'display_name' => 'My iPhone',
            'platform' => 'ios',
            'version' => '17.0',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $this->assertDatabaseHas('fcm_devices', [
            'uuid' => 'new-device-uuid',
            'notifyable_id' => $user->id,
        ]);
    }
}

class MiddlewareTestUser extends Model
{
    use HasFcmDevices;

    protected $table = 'users';

    protected $fillable = ['name'];
}
