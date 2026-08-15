<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Tag;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    use ResolvesAttachments;

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'category_id' => $this->category_id,
            // Категория записи, как раздел у темы форума
            'category'    => BlogResource::make($this->whenLoaded('category')),
            'text'        => absolutizeUrls($this->text),
            'url'         => $this->getViewUrl(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'rating'      => $this->rating,
            'vote'        => [
                'type'  => Article::$morphName,
                'id'    => $this->id,
                'value' => $this->getAttribute('vote'),
                'own'   => $this->user_id === getUser('id'),
            ],
            'visits'         => $this->visits,
            'comments_count' => $this->count_comments,
            'tags'           => $this->whenLoaded('tags', fn () => $this->tags->map(static fn (Tag $tag) => [
                'name' => $tag->name,
                'url'  => route('blogs.tag', ['tag' => urlencode($tag->name)]),
            ])),
            'user'       => AuthorResource::make($this->user),
            'media'      => FileResource::collection($this->resolveMedia($this->resource)),
            'files'      => FileResource::collection($this->resolveFiles($this->resource)),
            'created_at' => dateFixed($this->created_at, 'c', true),
            'updated_at' => dateFixed($this->updated_at, 'c', true),
        ];
    }
}
