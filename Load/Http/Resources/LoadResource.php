<?php

declare(strict_types=1);

namespace Modules\Load\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Load\Models\Load;

/** @mixin Load */
class LoadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'parent_id' => $this->parent_id,
            // Родительская категория приходит вложенной, когда связь загружена
            // (у корневых связь возвращает пустую модель-заглушку)
            'parent'      => $this->whenLoaded('parent', fn () => $this->parent->id ? self::make($this->parent) : null),
            'name'        => $this->name,
            'url'         => route('loads.load', ['id' => $this->id]),
            'closed'      => (bool) $this->closed,
            'downs_count' => $this->count_downs,
            // Подкатегории приходят только в списке категорий
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
