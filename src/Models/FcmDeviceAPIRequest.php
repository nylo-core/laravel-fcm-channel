<?php

namespace VeskoDigital\LaravelFCM\Models;

use Illuminate\Database\Eloquent\Model;

class FcmDeviceAPIRequest extends Model
{

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'fcm_device_api_requests';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id', 'fcm_user_device_id', 'path', 'ip', 'updated_at', 'created_at'
	];

	/**
	 * Get the device that owns the API Request.
	 */
	public function fcmUserDevice()
	{
		return $this->belongsTo(\App\Models\FcmUserDevice::class);
	}
}
