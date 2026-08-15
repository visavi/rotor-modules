<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Models\Offer;

/** @mixin Offer */
class OfferResource extends JsonResource
{
    use ResolvesAttachments;

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'status'      => $this->status,
            'title'       => $this->title,
            'text'        => absolutizeUrls($this->text),
            'url'         => $this->getViewUrl(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'rating'      => $this->rating,
            'vote'        => [
                'type'  => Offer::$morphName,
                'id'    => $this->id,
                'value' => $this->getAttribute('vote'),
                'own'   => $this->user_id === getUser('id'),
            ],
            'closed'         => (bool) $this->closed,
            'comments_count' => $this->count_comments,
            'user'           => AuthorResource::make($this->user),
            'media'          => FileResource::collection($this->resolveMedia($this->resource)),
            'files'          => FileResource::collection($this->resolveFiles($this->resource)),
            // Официальный ответ администрации, если он уже дан
            'reply' => $this->reply ? [
                'text'        => absolutizeUrls($this->reply),
                'user'        => AuthorResource::make($this->replyUser),
                'answered_at' => dateFixed($this->updated_at, 'c', true),
            ] : null,
            'created_at' => dateFixed($this->created_at, 'c', true),
            'updated_at' => dateFixed($this->updated_at, 'c', true),
        ];
    }
}
