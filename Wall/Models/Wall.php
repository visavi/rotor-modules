<?php

declare(strict_types=1);

namespace Modules\Wall\Models;

use App\Casts\HtmlCast;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property int             $user_id
 * @property int             $author_id
 * @property string          $text
 * @property CarbonImmutable $created_at
 * @property-read User $user
 */
class Wall extends Model
{
    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Morph name
     */
    public static string $morphName = 'walls';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'author_id' => 'int',
            'user_id'   => 'int',
            'text'      => HtmlCast::class,
        ];
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Возвращает связь владельца
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withDefault();
    }
}
