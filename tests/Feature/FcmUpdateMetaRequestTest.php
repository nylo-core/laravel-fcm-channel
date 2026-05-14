<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Nylo\LaravelFCM\Http\Requests\FcmUpdateMetaRequest;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class FcmUpdateMetaRequestTest extends TestCase
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

    public function test_authorize_fails_when_no_device_in_request()
    {
        $user = FcmMetaRequestTestUser::create(['name' => 'Test']);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn($user->id);

        $request = new FcmUpdateMetaRequest;
        $request->replace([]);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_fails_when_device_belongs_to_different_user()
    {
        $owner = FcmMetaRequestTestUser::create(['name' => 'Owner']);
        $attacker = FcmMetaRequestTestUser::create(['name' => 'Attacker']);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $owner->id,
            'notifyable_type' => FcmMetaRequestTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn($attacker->id);

        $request = new FcmUpdateMetaRequest;
        $request->replace(['device' => $device]);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_passes_when_device_belongs_to_authenticated_user()
    {
        $user = FcmMetaRequestTestUser::create(['name' => 'Test']);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => FcmMetaRequestTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn($user->id);

        $request = new FcmUpdateMetaRequest;
        $request->replace(['device' => $device]);

        $this->assertTrue($request->authorize());
    }

    public function test_validation_rules_are_correct()
    {
        $request = new FcmUpdateMetaRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('uuid', $rules);
        $this->assertArrayHasKey('model', $rules);
        $this->assertArrayHasKey('display_name', $rules);
        $this->assertArrayHasKey('platform', $rules);
        $this->assertArrayHasKey('version', $rules);
        $this->assertEquals('nullable|string', $rules['uuid']);
        $this->assertEquals('nullable|string', $rules['model']);
        $this->assertEquals('nullable|string', $rules['display_name']);
        $this->assertEquals('nullable|string', $rules['platform']);
        $this->assertEquals('nullable|string', $rules['version']);
    }
}

class FcmMetaRequestTestUser extends Model
{
    use HasFcmDevices;

    protected $table = 'users';

    protected $fillable = ['name'];
}
