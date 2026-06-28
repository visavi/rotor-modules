<?php

declare(strict_types=1);

namespace Modules\Load\Models;

use App\Traits\CategoryTreeTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Load
 *
 * @property int    $id
 * @property int    $sort
 * @property int    $parent_id
 * @property string $name
 * @property int    $count_downs
 * @property bool   $closed
 * @property int    $depth
 * @property mixed  $child
 * @property-read Load                  $parent
 * @property-read Collection<int, Load> $children
 */
class Load extends Model
{
    use CategoryTreeTrait;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

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
            'closed' => 'bool',
        ];
    }

    /**
     * Возвращает связь родительской категории
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withDefault();
    }

    /**
     * Возвращает связь подкатегорий
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    /**
     * Возвращает последнюю загрузку
     */
    public function lastDown(): HasOne
    {
        return $this->hasOne(Down::class, 'category_id')
            ->active()
            ->latest('created_at')
            ->limit(1);
    }

    /**
     * Возвращает количество загрузок за последние 3 дней
     */
    public function new(): HasOne
    {
        return $this->hasOne(Down::class, 'category_id')
            ->selectRaw('category_id, count(*) as count_downs')
            ->active()
            ->where('created_at', '>', now()->subDays(3))
            ->groupBy('category_id');
    }
}
