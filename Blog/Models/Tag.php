<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Tag
 *
 * @property int    $id
 * @property string $name
 */
class Tag extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tags', 'tag_id', 'article_id');
    }
}
