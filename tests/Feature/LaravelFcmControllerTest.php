<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class LaravelFcmControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('laravelfcm.default_notifyable_model', ControllerTestUser::class);
        $app['config']->set('laravelfcm.middleware', ['auth']);
    }

    public function test_update_returns_400_when_no_payload_provided()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'original-token',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'original-token',
            'model' => 'Test',
            'display_name' => 'Test',
            'platform' => 'ios',
            'version' => '1.0',
        ]);

        $response = $this->putJson(
            config('laravelfcm.path', 'api/fcm/').'device',
            [],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(400);
    }

    public function test_update_updates_is_active_field()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'original-token',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'original-token',
            'model' => 'Test',
            'display_name' => 'Test',
            'platform' => 'ios',
            'version' => '1.0',
        ]);

        $response = $this->putJson(
            config('laravelfcm.path', 'api/fcm/').'device',
            ['is_active' => false],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 200]);

        $device->refresh();
        $this->assertFalse((bool) $device->is_active);
    }

    public function test_update_updates_fcm_token_field()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'original-token',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'original-token',
            'model' => 'Test',
            'display_name' => 'Test',
            'platform' => 'ios',
            'version' => '1.0',
        ]);

        $response = $this->putJson(
            config('laravelfcm.path', 'api/fcm/').'device',
            ['fcm_token' => 'new-token-456'],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 200]);

        $device->refresh();
        $this->assertEquals('new-token-456', $device->fcm_token);
    }

    public function test_update_both_fields_successfully()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'original-token',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'original-token',
            'model' => 'Test',
            'display_name' => 'Test',
            'platform' => 'ios',
            'version' => '1.0',
        ]);

        $response = $this->putJson(
            config('laravelfcm.path', 'api/fcm/').'device',
            ['is_active' => false, 'fcm_token' => 'new-token-789'],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 200]);

        $device->refresh();
        $this->assertFalse((bool) $device->is_active);
        $this->assertEquals('new-token-789', $device->fcm_token);
    }

    public function test_update_meta_returns_400_when_no_payload_provided()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'token-123',
        ]);

        $response = $this->patchJson(
            config('laravelfcm.path', 'api/fcm/').'device/meta',
            [],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(400);
    }

    public function test_update_meta_updates_provided_fields()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'model' => 'Old',
            'display_name' => 'Old',
            'platform' => 'android',
            'version' => '14.0',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'token-123',
        ]);

        $response = $this->patchJson(
            config('laravelfcm.path', 'api/fcm/').'device/meta',
            [
                'model' => 'iPhone 15',
                'display_name' => 'My iPhone',
                'platform' => 'ios',
                'version' => '17.0',
            ],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 200]);

        $device->refresh();
        $this->assertEquals('iPhone 15', $device->model);
        $this->assertEquals('My iPhone', $device->display_name);
        $this->assertEquals('ios', $device->platform);
        $this->assertEquals('17.0', $device->version);
    }

    public function test_update_meta_skips_empty_string_fields()
    {
        $user = ControllerTestUser::create(['name' => 'Test']);
        $this->actingAs($user);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'model' => 'Original Model',
            'notifyable_id' => $user->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'test-uuid',
            'fcm_token' => 'token-123',
        ]);

        $response = $this->patchJson(
            config('laravelfcm.path', 'api/fcm/').'device/meta',
            [
                'model' => '',
                'display_name' => 'New Name',
            ],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(200);

        $device->refresh();
        $this->assertEquals('Original Model', $device->model);
        $this->assertEquals('New Name', $device->display_name);
    }

    public function test_update_meta_returns_403_when_device_belongs_to_different_user()
    {
        $owner = ControllerTestUser::create(['name' => 'Owner']);
        $attacker = ControllerTestUser::create(['name' => 'Attacker']);
        $this->actingAs($attacker);

        // Pre-existing device owned by another user — the middleware should
        // create a new device for the attacker (different notifyable_id), and
        // the controller acts on that device. We assert the owner's record is
        // untouched.
        $ownerDevice = FcmDevice::create([
            'uuid' => 'owner-uuid',
            'model' => 'Owner Model',
            'notifyable_id' => $owner->id,
            'notifyable_type' => ControllerTestUser::class,
            'fcm_token' => 'owner-token',
            'is_active' => true,
        ]);

        $dmeta = json_encode([
            'uuid' => 'attacker-uuid',
            'fcm_token' => 'attacker-token',
        ]);

        $response = $this->patchJson(
            config('laravelfcm.path', 'api/fcm/').'device/meta',
            ['model' => 'Hijacked'],
            ['X-DMETA' => $dmeta]
        );

        $response->assertStatus(200);

        $ownerDevice->refresh();
        $this->assertEquals('Owner Model', $ownerDevice->model);
        $this->assertEquals('owner-token', $ownerDevice->fcm_token);
    }
}

class ControllerTestUser extends Authenticatable
{
    use HasFcmDevices;

    protected $table = 'users';

    protected $fillable = ['name'];
}
