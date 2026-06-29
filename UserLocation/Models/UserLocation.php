<?php

declare(strict_types=1);

namespace Modules\UserLocation\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserLocation
 *
 * @property int             $id
 * @property int             $user_id
 * @property string          $path
 * @property string          $title
 * @property string|null     $ip
 * @property string|null     $brow
 * @property CarbonImmutable $created_at
 */
class UserLocation extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id'    => 'int',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
