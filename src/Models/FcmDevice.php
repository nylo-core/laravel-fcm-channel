<?php

namespace Nylo\LaravelFCM\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Nylo\LaravelFCM\Jobs\FcmSendNotificationJob;

/**
 * Class FcmDevice
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $model
 * @property string|null $display_name
 * @property string|null $platform
 * @property string|null $version
 * @property int $notifyable_id
 * @property string $notifyable_type
 * @property string|null $fcm_token
 * @property int $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class FcmDevice extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fcm_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'display_name',
        'model',
        'platform',
        'version',
        'notifyable_id',
        'notifyable_type',
        'fcm_token',
        'is_active',
    ];

    /**
     * Scope models with fcm push token.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithPushToken($query)
    {
        return $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
    }

    /**
     * Scope models which are active (not trashed).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Get the parent notifyable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function notifyable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Send a message to this model.
     *
     * @param  FcmMessage|array<string, mixed>  $message
     */
    public function sendFcmMessage(FcmMessage|array $message): void
    {
        if (! ($message instanceof FcmMessage)) {
            $message = FcmMessage::createFromArray($message);
        }
        FcmSendNotificationJob::dispatch($message, $this);
    }
}
