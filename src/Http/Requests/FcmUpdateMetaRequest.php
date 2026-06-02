<?php

namespace Nylo\LaravelFCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Nylo\LaravelFCM\Models\FcmDevice;

class FcmUpdateMetaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $device = $this->input('device');

        return Auth::check() &&
               $device instanceof FcmDevice &&
               $device->notifyable_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'uuid' => 'nullable|string',
            'model' => 'nullable|string',
            'display_name' => 'nullable|string',
            'platform' => 'nullable|string',
            'version' => 'nullable|string',
        ];
    }
}
