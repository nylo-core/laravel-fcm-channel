<?php

namespace Nylo\LaravelFCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FcmUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $device = $this->get('device');

        return Auth::check() &&
               $device &&
               $device->notifyable_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'is_active' => 'nullable|boolean',
            'fcm_token' => 'nullable|string',
        ];
    }
}
