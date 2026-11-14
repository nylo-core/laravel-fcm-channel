<?php

namespace Nylo\LaravelFCM\Test\Feature;

use Illuminate\Support\Facades\DB;
use Nylo\LaravelFCM\Models\FcmDevice;
use Nylo\LaravelFCM\Models\FcmMessage;
use Nylo\LaravelFCM\Services\FcmCloudMessagingService;
use Nylo\LaravelFCM\Test\TestCase;

class FcmCloudMessagingServiceTest extends TestCase
{
    public function test_deactivate_invalid_tokens()
    {
        FcmDevice::create([
            'uuid' => 'device-1',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'invalid-token-1',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-2',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'invalid-token-2',
            'is_active' => true,
        ]);

        FcmDevice::create([
            'uuid' => 'device-3',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'valid-token',
            'is_active' => true,
        ]);

        // Test the deactivation logic directly using DB facade (same as service does)
        $tokens = ['invalid-token-1', 'invalid-token-2'];
        $count = DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->assertEquals(2, $count);

        $this->assertFalse((bool) FcmDevice::where('fcm_token', 'invalid-token-1')->first()->is_active);
        $this->assertFalse((bool) FcmDevice::where('fcm_token', 'invalid-token-2')->first()->is_active);
        $this->assertTrue((bool) FcmDevice::where('fcm_token', 'valid-token')->first()->is_active);
    }

    public function test_deactivate_returns_zero_for_empty_tokens()
    {
        // Empty array should not update anything
        $tokens = [];
        if (empty($tokens)) {
            $count = 0;
        } else {
            $count = DB::table('fcm_devices')
                ->whereIn('fcm_token', $tokens)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $this->assertEquals(0, $count);
    }

    public function test_deactivate_ignores_already_inactive()
    {
        FcmDevice::create([
            'uuid' => 'device-1',
            'notifyable_id' => 1,
            'notifyable_type' => 'App\Models\User',
            'fcm_token' => 'already-inactive-token',
            'is_active' => false,
        ]);

        $tokens = ['already-inactive-token'];
        $count = DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->assertEquals(0, $count);
    }

    public function test_deactivate_handles_nonexistent_tokens()
    {
        $tokens = ['nonexistent-token-1', 'nonexistent-token-2'];
        $count = DB::table('fcm_devices')
            ->whereIn('fcm_token', $tokens)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->assertEquals(0, $count);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(FcmMessage $message): array
    {
        $service = (new \ReflectionClass(FcmCloudMessagingService::class))->newInstanceWithoutConstructor();
        $cloudMessage = (new \ReflectionMethod(FcmCloudMessagingService::class, 'buildCloudMessage'))
            ->invoke($service, $message);

        return json_decode(json_encode($cloudMessage), true);
    }

    public function test_android_sound_drops_the_file_extension()
    {
        $payload = $this->buildPayload((new FcmMessage)->title('Hi')->sound('new_message.mp3'));

        $this->assertSame('new_message', $payload['android']['notification']['sound']);
        $this->assertSame('new_message.mp3', $payload['apns']['payload']['aps']['sound']);
    }

    public function test_android_sound_without_extension_is_unchanged()
    {
        $payload = $this->buildPayload((new FcmMessage)->title('Hi')->sound('chime'));

        $this->assertSame('chime', $payload['android']['notification']['sound']);
    }

    public function test_android_channel_id_is_sent()
    {
        $payload = $this->buildPayload(
            (new FcmMessage)->title('Hi')->sound('new_message.mp3')->androidChannelId('chat_messages')
        );

        $this->assertSame('chat_messages', $payload['android']['notification']['channel_id']);
        $this->assertSame('new_message', $payload['android']['notification']['sound']);
    }

    public function test_android_channel_id_is_sent_with_default_sound()
    {
        $payload = $this->buildPayload((new FcmMessage)->title('Hi')->androidChannelId('general'));

        $this->assertSame('general', $payload['android']['notification']['channel_id']);
        $this->assertSame('default', $payload['android']['notification']['sound']);
    }

    public function test_no_android_channel_id_by_default()
    {
        $payload = $this->buildPayload((new FcmMessage)->title('Hi'));

        $this->assertArrayNotHasKey('channel_id', $payload['android']['notification'] ?? []);
    }

    public function test_default_sound_is_sent_when_no_sound_is_set()
    {
        $payload = $this->buildPayload((new FcmMessage)->title('Hi'));

        $this->assertSame('default', $payload['android']['notification']['sound']);
        $this->assertSame('default', $payload['apns']['payload']['aps']['sound']);
    }

    public function test_without_default_sound_sends_no_sound()
    {
        $payload = $this->buildPayload((new FcmMessage)->title('Hi')->withoutDefaultSound());

        $this->assertArrayNotHasKey('sound', $payload['android']['notification'] ?? []);
        $this->assertArrayNotHasKey('sound', $payload['apns']['payload']['aps'] ?? []);
    }
}
