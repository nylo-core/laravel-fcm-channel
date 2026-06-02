<?php

namespace Nylo\LaravelFCM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Nylo\LaravelFCM\Http\Requests\FcmUpdateMetaRequest;
use Nylo\LaravelFCM\Http\Requests\FcmUpdateRequest;
use Nylo\LaravelFCM\Models\FcmDevice;

class LaravelFcmController extends Controller
{
    /**
     * Update a FcmDevice
     *
     *
     * @return JsonResponse
     */
    public function update(FcmUpdateRequest $request)
    {
        $updatePayload = [];
        if ($request->has('is_active')) {
            $updatePayload['is_active'] = $request->is_active;
        }

        if ($request->has('fcm_token')) {
            $updatePayload['fcm_token'] = $request->fcm_token;
        }

        abort_if(empty($updatePayload), 400);

        $device = $request->input('device');
        abort_unless($device instanceof FcmDevice, 400);

        $didUpdate = $device->update($updatePayload);

        return response()->json(['status' => $didUpdate ? 200 : 500]);
    }

    /**
     * Update the metadata of a FcmDevice (uuid, model, display_name, platform, version).
     *
     * @return JsonResponse
     */
    public function updateMeta(FcmUpdateMetaRequest $request)
    {
        $updatePayload = [];
        foreach (['uuid', 'model', 'display_name', 'platform', 'version'] as $key) {
            if ($request->filled($key)) {
                $updatePayload[$key] = $request->input($key);
            }
        }

        abort_if(empty($updatePayload), 400);

        $device = $request->input('device');
        abort_unless($device instanceof FcmDevice, 400);

        $didUpdate = $device->update($updatePayload);

        return response()->json(['status' => $didUpdate ? 200 : 500]);
    }
}
