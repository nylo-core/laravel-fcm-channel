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

        $middleware = new AppApiRequestMiddleware;
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
        $middleware = new AppApiRequestMiddleware;
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
        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-DMETA', '{}');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_creates_new_device_when_uuid_not_found()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);
        $middleware = new AppApiRequestMiddleware;
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

        $middleware = new AppApiRequestMiddleware;
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

        $middleware = new AppApiRequestMiddleware;
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

    public function test_returns_400_when_uuid_is_missing_from_x_dmeta()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('FCM middleware received X-DMETA without a valid uuid');

        $user = MiddlewareTestUser::create(['name' => 'Test']);
        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-DMETA', json_encode(['model' => 'iPhone 15']));

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_returns_400_when_uuid_is_empty_string()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('FCM middleware received X-DMETA without a valid uuid');

        $user = MiddlewareTestUser::create(['name' => 'Test']);
        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-DMETA', json_encode(['uuid' => '']));

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_persists_fcm_token_when_creating_new_device()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'POST', ['fcm_token' => 'fresh-token']);
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'fresh-uuid',
            'model' => 'iPhone 15',
            'platform' => 'ios',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $this->assertDatabaseHas('fcm_devices', [
            'uuid' => 'fresh-uuid',
            'fcm_token' => 'fresh-token',
            'notifyable_id' => $user->id,
            'is_active' => 1,
        ]);
    }

    public function test_finds_existing_device_by_uuid_when_no_token_match()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $existingDevice = FcmDevice::create([
            'uuid' => 'matching-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => null,
            'is_active' => true,
        ]);

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'matching-uuid',
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

    public function test_updates_meta_fields_on_existing_device()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $existingDevice = FcmDevice::create([
            'uuid' => 'matching-uuid',
            'model' => 'Old Model',
            'display_name' => 'Old Name',
            'platform' => 'android',
            'version' => '14.0',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'matching-uuid',
            'fcm_token' => 'token-123',
            'model' => 'iPhone 15',
            'display_name' => 'My iPhone',
            'platform' => 'ios',
            'version' => '17.0',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $existingDevice->refresh();
        $this->assertEquals('iPhone 15', $existingDevice->model);
        $this->assertEquals('My iPhone', $existingDevice->display_name);
        $this->assertEquals('ios', $existingDevice->platform);
        $this->assertEquals('17.0', $existingDevice->version);
    }

    public function test_updates_fcm_token_and_reactivates_when_token_changes()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $existingDevice = FcmDevice::create([
            'uuid' => 'matching-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => 'old-token',
            'is_active' => false,
        ]);

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'POST', ['fcm_token' => 'new-token']);
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode(['uuid' => 'matching-uuid']);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $existingDevice->refresh();
        $this->assertEquals('new-token', $existingDevice->fcm_token);
        $this->assertTrue((bool) $existingDevice->is_active);
    }

    public function test_does_not_change_is_active_when_token_unchanged()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $existingDevice = FcmDevice::create([
            'uuid' => 'matching-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => 'same-token',
            'is_active' => false,
        ]);

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'POST', ['fcm_token' => 'same-token']);
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode(['uuid' => 'matching-uuid']);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $existingDevice->refresh();
        $this->assertEquals('same-token', $existingDevice->fcm_token);
        $this->assertFalse((bool) $existingDevice->is_active);
    }

    public function test_restores_soft_deleted_device_when_token_matches()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $device = FcmDevice::create([
            'uuid' => 'old-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => 'persistent-token',
            'is_active' => false,
        ]);
        $device->delete();
        $deviceId = $device->id;

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'POST', ['fcm_token' => 'persistent-token']);
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'new-uuid',
            'model' => 'iPhone 15',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $restored = FcmDevice::find($deviceId);
        $this->assertNotNull($restored, 'Soft-deleted device should be restored, not blocked by unique index');
        $this->assertNull($restored->deleted_at);
        $this->assertTrue((bool) $restored->is_active);
        $this->assertEquals('new-uuid', $restored->uuid);
    }

    public function test_restores_soft_deleted_device_when_uuid_matches()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        $device = FcmDevice::create([
            'uuid' => 'persistent-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => null,
            'is_active' => false,
        ]);
        $device->delete();
        $deviceId = $device->id;

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'persistent-uuid',
            'model' => 'iPhone 15',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $restored = FcmDevice::find($deviceId);
        $this->assertNotNull($restored, 'Soft-deleted device should be restored when matched by uuid');
        $this->assertNull($restored->deleted_at);
        $this->assertTrue((bool) $restored->is_active);
    }

    public function test_token_lookup_is_scoped_by_notifyable_type()
    {
        $user = MiddlewareTestUser::create(['name' => 'Test']);

        // A row belonging to a different notifyable model with the same id/token.
        // The middleware must NOT match this when looking up by token, because the
        // unique constraint scopes by notifyable_type and matching across types
        // would leak another model's device to this user's request.
        FcmDevice::create([
            'uuid' => 'other-model-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => 'App\\Models\\SomeOtherModel',
            'fcm_token' => 'shared-token',
            'is_active' => true,
        ]);

        $middleware = new AppApiRequestMiddleware;
        $request = Request::create('/test', 'POST', ['fcm_token' => 'shared-token']);
        $request->setUserResolver(fn () => $user);

        $dmeta = json_encode([
            'uuid' => 'this-user-uuid',
            'model' => 'iPhone 15',
            'platform' => 'ios',
        ]);
        $request->headers->set('X-DMETA', $dmeta);

        $middleware->handle($request, fn () => response('ok'));

        $this->assertDatabaseHas('fcm_devices', [
            'uuid' => 'this-user-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => MiddlewareTestUser::class,
            'fcm_token' => 'shared-token',
        ]);
    }
}

class MiddlewareTestUser extends Model
{
    use HasFcmDevices;

    protected $table = 'users';

    protected $fillable = ['name'];
}
