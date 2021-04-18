<?php

declare(strict_types=1);

namespace Modules\Gift\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Gift
 *
 * @property int id
 * @property int gift_id
 * @property int user_id
 * @property int send_user_id
 * @property string text
 * @property int created_at
 * @property int deleted_at
 */
class GiftsUser extends BaseModel
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Возвращает связанный подарок
     *
     * @return BelongsTo
     */
    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class)->withDefault();
    }

    /**
     * Возвращает связь пользователей
     *
     * @return BelongsTo
     */
    public function sendUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'send_user_id')->withDefault();
    }
}
