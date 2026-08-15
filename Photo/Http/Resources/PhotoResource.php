<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Photo\Models\Photo;

/** @mixin Photo */
class PhotoResource extends JsonResource
{
    use ResolvesAttachments;

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'text'        => $this->text ? absolutizeUrls($this->text) : null,
            'url'         => $this->getViewUrl(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'rating'      => $this->rating,
            'vote'        => [
                'type'  => Photo::$morphName,
                'id'    => $this->id,
                'value' => $this->getAttribute('vote'),
                'own'   => $this->user_id === getUser('id'),
            ],
            // Комментирование закрыто
            'closed'         => (bool) $this->closed,
            'comments_count' => $this->count_comments,
            'user'           => AuthorResource::make($this->user),
            // Снимки — само содержимое записи, поэтому в галерею идут все,
            // включая вставленные в текст
            'media'      => FileResource::collection($this->getMedia()),
            'files'      => FileResource::collection($this->resolveFiles($this->resource)),
            'created_at' => dateFixed($this->created_at, 'c', true),
            'updated_at' => dateFixed($this->updated_at, 'c', true),
        ];
    }
}
