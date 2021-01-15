<?php

namespace WooSignal\LaravelFCM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use WooSignal\LaravelFCM\Domain\UserDevice\UserDevice;
use App\Models\AppAPIRequest;

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
        $path = $request->path();

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
                    'brand' => $dMeta['brand'],
                    'os' => $dMeta['os'],
                    'version' => $dMeta['version'],
                    'user_id' => $request->user()->id,
                    'is_active' => 1
                ]
            );
        
            $request->request->add(['device' => $device]);
            return $next($request);
        }

        abort(401);
    }
}
