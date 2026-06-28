<?php

declare(strict_types=1);

namespace Modules\Surprise\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Surprise
 *
 * @property int             $id
 * @property int             $user_id
 * @property int             $year
 * @property CarbonImmutable $created_at
 */
class Surprise extends Model
{
    /**
     * Morph name
     */
    public static string $morphName = 'surprises';

    /**
     * The table associated with the model.
     */
    protected $table = 'surprise';

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

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
            'user_id' => 'int',
            'year'    => 'int',
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
