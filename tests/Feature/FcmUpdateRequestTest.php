<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Nylo\LaravelFCM\Http\Requests\FcmUpdateRequest;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Test\TestCase;
use Nylo\LaravelFCM\Traits\HasFcmDevices;

class FcmUpdateRequestTest extends TestCase
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
        $user = FcmRequestTestUser::create(['name' => 'Test']);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn($user->id);

        $request = new FcmUpdateRequest();
        $request->replace([]);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_fails_when_device_belongs_to_different_user()
    {
        $owner = FcmRequestTestUser::create(['name' => 'Owner']);
        $attacker = FcmRequestTestUser::create(['name' => 'Attacker']);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $owner->id,
            'notifyable_type' => FcmRequestTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn($attacker->id);

        $request = new FcmUpdateRequest();
        $request->replace(['device' => $device]);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_passes_when_device_belongs_to_authenticated_user()
    {
        $user = FcmRequestTestUser::create(['name' => 'Test']);

        $device = FcmDevice::create([
            'uuid' => 'test-uuid',
            'notifyable_id' => $user->id,
            'notifyable_type' => FcmRequestTestUser::class,
            'fcm_token' => 'token-123',
            'is_active' => true,
        ]);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn($user->id);

        $request = new FcmUpdateRequest();
        $request->replace(['device' => $device]);

        $this->assertTrue($request->authorize());
    }

    public function test_validation_rules_are_correct()
    {
        $request = new FcmUpdateRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('is_active', $rules);
        $this->assertArrayHasKey('fcm_token', $rules);
        $this->assertEquals('nullable|boolean', $rules['is_active']);
        $this->assertEquals('nullable|string', $rules['fcm_token']);
    }
}

class FcmRequestTestUser extends Model
{
    use HasFcmDevices;

    protected $table = 'users';

    protected $fillable = ['name'];
}
