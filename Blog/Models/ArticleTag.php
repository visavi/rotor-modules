<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ArticleTag
 *
 * @property int $id
 * @property int $article_id
 * @property int $tag_id
 */
class ArticleTag extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
