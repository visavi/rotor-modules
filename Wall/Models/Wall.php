<?php

declare(strict_types=1);

namespace Modules\Wall\Models;

use App\Casts\HtmlCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $user_id
 * @property int    $author_id
 * @property string $text
 * @property int    $created_at
 */
class Wall extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public static string $morphName = 'walls';

    protected function casts(): array
    {
        return [
            'author_id' => 'int',
            'user_id'   => 'int',
            'text'      => HtmlCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withDefault();
    }
}
