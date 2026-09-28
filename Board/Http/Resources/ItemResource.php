<?php

declare(strict_types=1);

namespace Modules\Board\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Board\Models\Item;

/** @mixin Item */
class ItemResource extends JsonResource
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
            'category_id' => $this->board_id,
            // Категория записи, как раздел у темы форума
            'category' => BoardResource::make($this->whenLoaded('category')),
            'price'    => $this->price,
            // Валюта общая для сайта, отдельного поля у объявления нет
            'currency' => setting('currency'),
            // Гостю номер не отдаём: без токена его собрал бы любой парсер
            'phone' => getUser() ? $this->phone : null,
            'city'  => $this->city,
            // Ключи мессенджеров, включённых на сайте; ссылки клиент строит по номеру
            'messengers' => array_keys($this->getMessengers()),
            'active'     => (bool) $this->active,
            'visits'     => $this->visits,
            'user'       => AuthorResource::make($this->user),
            'media'      => FileResource::collection($this->resolveMedia($this->resource)),
            'files'      => FileResource::collection($this->resolveFiles($this->resource)),
            'created_at' => dateFixed($this->created_at, 'c', true),
            'updated_at' => dateFixed($this->updated_at, 'c', true),
            // Объявление живёт ограниченный срок, после чего пропадает из списков
            'expires_at' => dateFixed($this->expires_at, 'c', true),
            'expired'    => $this->expires_at->lte(now()),
        ];
    }
}
