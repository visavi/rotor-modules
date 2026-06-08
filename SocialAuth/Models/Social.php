<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Social
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string $token
 * @property string $refresh_token
 * @property int    $created_at
 */
class Social extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
