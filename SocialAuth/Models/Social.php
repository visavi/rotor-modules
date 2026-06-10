<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Social
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string $token
 * @property Carbon $created_at
 */
class Social extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
