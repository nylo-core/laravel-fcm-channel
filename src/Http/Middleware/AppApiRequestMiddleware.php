<?php

namespace WooSignal\LaravelFCM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use WooSignal\LaravelFCM\Models\UserDevice;
use WooSignal\LaravelFCM\Models\AppAPIRequest;

class AppApiRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!is_string($request->header('X-DMeta'))) {
            abort(401);
        }

        $dMeta = json_decode($request->header('X-DMeta'), true);

        if (!empty($dMeta)) {
            // get device
            $device = UserDevice::firstOrCreate(
                ['uuid' => $dMeta['uuid']],
                [
                    'uuid' => $dMeta['uuid'],
                    'model' => $dMeta['model'],
                    'display_name' => $dMeta['display_name'],
                    'platform' => $dMeta['platform'],
                    'version' => $dMeta['version'],
                    'notifyable_id' => $request->user()->id,
                    'notifyable_type' => config('laravelfcm.default_notifyable_model', 'App\Models\User'),
                    'is_active' => 1
                ]
            );

            $appApiRequest = AppAPIRequest::create([
                'user_device_id' => $device->id,
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            $request->request->add(['device' => $device]);
            return $next($request);
        }

        abort(401);
    }
}
