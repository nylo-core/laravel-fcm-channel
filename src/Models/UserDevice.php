<?php

namespace WooSignal\LaravelFCM\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{

    protected $table = 'user_devices';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'display_name',
        'model',
        'brand',
        'manufacturer',
        'version',
        'notifyable_id',
        'notifyable_type',
        'push_token',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [

    ];

    public function scopeWithPushToken($query)
    {
        return $query->whereNotNull('push_token')->where('push_token', '!=', '');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Get the parent notifyable model.
     */
    public function notifyable()
    {
        return $this->morphTo();
    }
}
