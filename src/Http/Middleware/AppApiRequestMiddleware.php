<?php

namespace Nylo\LaravelFCM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nylo\LaravelFCM\Models\FcmDevice;

class AppApiRequestMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        if (empty($user)) {
            Log::info('FCM middleware did not receive an authenticated user');
            abort(403);
        }

        $deviceMeta = $request->header('X-DMETA');
        if (! is_string($deviceMeta)) {
            Log::info('FCM middleware received a malformed X-DMeta header');
            abort(400);
        }

        $dMeta = json_decode($deviceMeta, true);

        if (empty($dMeta)) {
            Log::info('FCM middleware has empty X-DMETA data');
            abort(400);
        }

        if (! isset($dMeta['uuid']) || ! is_string($dMeta['uuid']) || $dMeta['uuid'] === '') {
            Log::info('FCM middleware received X-DMETA without a valid uuid');
            abort(400);
        }

        try {
            DB::transaction(function () use ($user, $dMeta, &$request) {
                if ($request->has('fcm_token')) {
                    $dMeta['fcm_token'] = $request->fcm_token;
                }

                $notifyableType = config('laravelfcm.default_notifyable_model', 'App\Models\User');

                // Lookups include trashed rows: the unique index
                // (fcm_token, notifyable_id, notifyable_type) doesn't filter by
                // deleted_at, so a soft-deleted row would block re-registration
                // of the same token. Find it, then restore() on the update path.
                /** @var FcmDevice|null $device */
                $device = null;
                if (! empty($dMeta['fcm_token'])) {
                    $device = FcmDevice::withTrashed()
                        ->where('fcm_token', $dMeta['fcm_token'])
                        ->where('notifyable_id', $user->id)
                        ->where('notifyable_type', $notifyableType)
                        ->first();
                }

                if (empty($device)) {
                    $device = FcmDevice::withTrashed()
                        ->where('uuid', $dMeta['uuid'])
                        ->where('notifyable_id', $user->id)
                        ->where('notifyable_type', $notifyableType)
                        ->first();
                }

                if (empty($device)) {
                    $device = FcmDevice::create([
                        'uuid' => $dMeta['uuid'],
                        'model' => $dMeta['model'] ?? null,
                        'display_name' => $dMeta['display_name'] ?? null,
                        'platform' => $dMeta['platform'] ?? null,
                        'version' => $dMeta['version'] ?? null,
                        'notifyable_id' => $user->id,
                        'notifyable_type' => $notifyableType,
                        'fcm_token' => $dMeta['fcm_token'] ?? null,
                        'is_active' => 1,
                    ]);

                    $request->request->add(['device' => $device]);

                    return;
                }

                $update = [];

                if ($device->trashed()) {
                    $device->restore();
                    $update['is_active'] = 1;
                }

                foreach (['uuid', 'model', 'display_name', 'platform', 'version'] as $key) {
                    if (isset($dMeta[$key]) && $dMeta[$key] !== $device->{$key}) {
                        $update[$key] = $dMeta[$key];
                    }
                }

                if (! empty($dMeta['fcm_token']) && $dMeta['fcm_token'] !== $device->fcm_token) {
                    $update['fcm_token'] = $dMeta['fcm_token'];
                    $update['is_active'] = 1;
                }

                if (! empty($update)) {
                    $device->update($update);
                }

                $request->request->add(['device' => $device]);
            });

            return $next($request);
        } catch (\Throwable $e) {
            Log::error('FCM middleware error: '.$e->getMessage(), ['exception' => $e]);
            abort(400);
        }
    }
}
