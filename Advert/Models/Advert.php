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
 * @property int             $bold
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
        if (! setting('rekusershow')) {
            return [];
        }

        return Cache::remember('adverts', 1800, static function () {
            $data = self::query()
                ->where('type', self::TYPE_USER)
                ->where('deleted_at', '>', now())
                ->get();

            if ($data->isEmpty()) {
                return [];
            }

            $links = [];
            foreach ($data as $val) {
                $name = check($val->name);

                if ($val->color) {
                    $name = '<span style="color:' . $val->color . '">' . $name . '</span>';
                }

                $link = '<a href="' . $val->site . '" target="_blank" rel="nofollow">' . $name . '</a>';

                if ($val->bold) {
                    $link = '<b>' . $link . '</b>';
                }

                $links[] = $link;
            }

            return $links;
        });
    }

    /**
     * Кеширует ссылки админской рекламы
     */
    public static function statAdminAdverts(): array
    {
        return Cache::remember('adminAdverts', 1800, static function () {
            $data = self::query()
                ->where('type', self::TYPE_ADMIN)
                ->where('deleted_at', '>', now())
                ->get();

            $links = [];
            if ($data->isNotEmpty()) {
                foreach ($data as $val) {
                    $name = check($val->name);

                    if ($val->color) {
                        $name = '<span style="color:' . $val->color . '">' . $name . '</span>';
                    }

                    $link = '<a href="' . $val->site . '" target="_blank">' . $name . '</a>';

                    if ($val->bold) {
                        $link = '<b>' . $link . '</b>';
                    }

                    $links[] = $link;
                }
            }

            return $links;
        });
    }
}
