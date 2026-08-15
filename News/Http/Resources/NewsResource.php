<?php

declare(strict_types=1);

namespace Modules\News\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\News\Models\News;

/** @mixin News */
class NewsResource extends JsonResource
{
    use ResolvesAttachments;

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'text'        => absolutizeUrls($this->text),
            'url'         => $this->getViewUrl(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'rating'      => $this->rating,
            'vote'        => [
                'type'  => News::$morphName,
                'id'    => $this->id,
                'value' => $this->getAttribute('vote'),
                'own'   => $this->user_id === getUser('id'),
            ],
            // Закрытая новость не принимает новые комментарии
            'closed'         => (bool) $this->closed,
            'top'            => (bool) $this->top,
            'comments_count' => $this->count_comments,
            'user'           => AuthorResource::make($this->user),
            'media'          => FileResource::collection($this->resolveMedia($this->resource)),
            'files'          => FileResource::collection($this->resolveFiles($this->resource)),
            'created_at'     => dateFixed($this->created_at, 'c', true),
            'updated_at'     => dateFixed($this->updated_at, 'c', true),
        ];
    }
}
