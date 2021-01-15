<?php

namespace WooSignal\LaravelFCM\Models;

use Illuminate\Database\Eloquent\Model;

class AppAPIRequest extends Model
{

	protected $table = 'api_app_requests';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id', 'user_device_id', 'path', 'ip', 'updated_at', 'created_at'
	];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [
	];

	public function userDevice()
	{
		return $this->belongsTo(\App\Models\UserDevice::class);
	}
}
