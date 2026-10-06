<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

/**
 * Class Tag
 *
 * @property int    $id
 * @property string $name
 */
class Tag extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Articles
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tags', 'tag_id', 'article_id');
    }

    /**
     * Облако: 100 самых частых тегов, имя => число статей, по убыванию.
     * Кэш на час сбрасывает сохранение статьи (clearCache('tagCloud'))
     *
     * @return array<int|string, int>
     */
    public static function cloud(): array
    {
        return Cache::remember('tagCloud', 3600, static fn () => self::query()
            ->withPublishedCount()
            ->orderByDesc('articles_count')
            ->limit(100)
            ->get()
            ->pluck('articles_count', 'name')
            ->all());
    }

    /**
     * Подсказки по началу слова: самые частые первыми
     *
     * @return Collection<int, self>
     */
    public static function suggest(string $query, int $limit = 10): Collection
    {
        return self::query()
            ->where('name', 'like', $query . '%')
            ->withPublishedCount()
            ->orderByDesc('articles_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Только теги опубликованных статей, в articles_count — их число:
     * тег одних черновиков вёл бы на пустой список
     */
    public function scopeWithPublishedCount(Builder $query): Builder
    {
        return $query
            ->whereHas('articles', static fn (Builder $query) => $query->where('articles.active', true))
            ->withCount(['articles' => static fn (Builder $query) => $query->where('articles.active', true)]);
    }
}
