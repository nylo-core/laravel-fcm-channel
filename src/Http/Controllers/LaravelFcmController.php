<?php

namespace Nylo\LaravelFCM\Http\Controllers;

use Nylo\LaravelFCM\Http\Controllers\Controller;
use Nylo\LaravelFCM\Http\Requests\FcmUpdateRequest;

class LaravelFcmController extends Controller
{
    /**
     * Update a FcmDevice
     *
     * @param FcmUpdateRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
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

        $didUpdate = $request->device->update($updatePayload);
        return response()->json(['status' => $didUpdate ? 200 : 500]);
    }
}
