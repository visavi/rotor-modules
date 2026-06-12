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
 * @property-read ?User $user
 */
class Social extends Model
{
    public const UPDATED_AT = null;

    public const array PROVIDERS = [
        'google' => ['name' => 'Google', 'icon' => 'fab fa-google fa-2x', 'color' => '#4285F4'],
        'github' => ['name' => 'GitHub', 'icon' => 'fab fa-github fa-2x', 'color' => '#8b949e'],
        'yandex' => ['name' => 'Яндекс', 'icon' => 'fab fa-yandex fa-2x', 'color' => '#FC3F1D'],
        'vk'     => ['name' => 'ВКонтакте', 'icon' => 'fab fa-vk fa-2x', 'color' => '#0077FF'],
    ];

    protected $guarded = [];

    protected $casts = [
        'token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get provider config
     */
    public static function providerConfig(string $provider): array
    {
        return self::PROVIDERS[$provider] ?? ['name' => ucfirst($provider), 'icon' => 'fab fa-' . $provider . ' fa-2x', 'color' => 'currentColor'];
    }
}
