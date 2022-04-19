<?php

namespace VeskoDigital\LaravelFCM\Http\Controllers;

use VeskoDigital\LaravelFCM\Http\Controllers\Controller;
use VeskoDigital\LaravelFCM\Http\Requests\FcmUpdateRequest;
use Illuminate\Http\Request;
use VeskoDigital\LaravelFCM\Models\FcmUserDevice;

class LaravelFcmController extends Controller
{
    /**
     * Update a FCMUserDevice
     */
    public function update(FcmUpdateRequest $request)
    {
        $updatePayload = [];
        if ($request->has('is_active')) {
            $updatePayload['is_active'] = $request->is_active;
        }

        if ($request->has('push_token')) {
            $updatePayload['push_token'] = $request->push_token;
        }

        abort_if(empty($updatePayload), 400);

        $didUpdate = $request->device->update($updatePayload);
        return response()->json(['status' => $didUpdate ? 200 : 500]);
    }

    /**
     * Store an FCMUserDevice.
     */
    public function store(Request $request)
    {
        $userId = auth()->user()->id;
        if (!$request->has('uuid')) {
            return response()->json(['status' => 400]);
        }

        $uuid = $request->uuid;

        $updatePayload = [
            'uuid' => $uuid,
            'is_active' => 1,
            'notifyable_id' => $userId,
            'notifyable_type' => config('laravelfcm.default_notifyable_model', 'App\Models\User'),
        ];
        
        if ($request->has('model')) {
            $updatePayload['model'] = $request->model;
        }

        if ($request->has('display_name')) {
            $updatePayload['display_name'] = $request->display_name;
        }

        if ($request->has('platform')) {
            $updatePayload['platform'] = $request->platform;
        }

        if ($request->has('version')) {
            $updatePayload['version'] = $request->version;
        }

        if ($request->has('is_active')) {
            $updatePayload['is_active'] = $request->is_active;
        }

        $fcmUserDevice = FcmUserDevice::firstOrCreate(
            [
                'uuid' => $uuid,
                'notifyable_id' => $userId,
            ],
            $updatePayload
        );

        return response()->json(['status' => $fcmUserDevice ? 200 : 500]);
    }
}