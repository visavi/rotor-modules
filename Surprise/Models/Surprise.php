<?php

declare(strict_types=1);

namespace Modules\Surprise\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Surprise
 *
 * @property int $id
 * @property int $user_id
 * @property int $year
 * @property int $created_at
 */
class Surprise extends Model
{
    public static string $morphName = 'surprises';

    protected $table = 'surprise';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'year'    => 'int',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
