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

        try {
            DB::transaction(function () use ($user, $dMeta, &$request) {
                if ($request->has('fcm_token')) {
                    $dMeta['fcm_token'] = $request->fcm_token;
                }

                if (! empty($dMeta['fcm_token'])) {
                    $device = FcmDevice::where('fcm_token', $dMeta['fcm_token'])
                        ->where('notifyable_id', $user->id)
                        ->first();
                    if (! empty($device)) {
                        $request->request->add(['device' => $device]);

                        return;
                    }
                }

                // get device
                $device = FcmDevice::firstOrCreate(
                    [
                        'uuid' => $dMeta['uuid'],
                        'notifyable_id' => $user->id,
                        'notifyable_type' => config('laravelfcm.default_notifyable_model', 'App\Models\User'),
                    ],
                    [
                        'uuid' => $dMeta['uuid'],
                        'model' => $dMeta['model'],
                        'display_name' => $dMeta['display_name'],
                        'platform' => $dMeta['platform'],
                        'version' => $dMeta['version'],
                        'notifyable_id' => $user->id,
                        'notifyable_type' => config('laravelfcm.default_notifyable_model', 'App\Models\User'),
                        'is_active' => 1,
                    ]
                );

                $request->request->add(['device' => $device]);
            });

            return $next($request);
        } catch (\Throwable $e) {
            Log::error('FCM middleware error: '.$e->getMessage(), ['exception' => $e]);
            abort(400);
        }
    }
}
