<?php

declare(strict_types=1);

namespace Modules\Advert\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Class Advert
 *
 * @property int             $id
 * @property string          $site
 * @property string          $name
 * @property string          $color
 * @property bool            $bold
 * @property string          $type
 * @property int             $user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $deleted_at
 */
class Advert extends Model
{
    public const string TYPE_USER = 'user';
    public const string TYPE_ADMIN = 'admin';

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
            'user_id'    => 'int',
            'bold'       => 'bool',
            'deleted_at' => 'datetime',
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
     * Кеширует ссылки пользовательской рекламы
     */
    public static function statUserAdverts(): array
    {
        if (! setting('rekuseractive')) {
            return [];
        }

        return Cache::remember('adverts', 1800, static fn () => self::buildLinks(self::TYPE_USER));
    }

    /**
     * Кеширует ссылки админской рекламы
     */
    public static function statAdminAdverts(): array
    {
        return Cache::remember('adminAdverts', 1800, static fn () => self::buildLinks(self::TYPE_ADMIN));
    }

    /**
     * Формирует html ссылок рекламы
     */
    private static function buildLinks(string $type): array
    {
        $adverts = self::query()
            ->where('type', $type)
            ->where('deleted_at', '>', now())
            ->get();

        $links = [];
        foreach ($adverts as $advert) {
            $name = check($advert->name);

            if ($advert->color) {
                $name = '<span style="color:' . e($advert->color) . '">' . $name . '</span>';
            }

            $rel = $type === self::TYPE_USER ? ' rel="nofollow"' : '';
            $link = '<a href="' . e($advert->site) . '" target="_blank"' . $rel . '>' . $name . '</a>';

            if ($advert->bold) {
                $link = '<b>' . $link . '</b>';
            }

            $links[] = $link;
        }

        return $links;
    }
}
