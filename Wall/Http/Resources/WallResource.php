<?php

declare(strict_types=1);

namespace Modules\Wall\Http\Resources;

use App\Http\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wall\Models\Wall;

/** @mixin Wall */
class WallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'text' => absolutizeUrls($this->text),
            'url'  => route('walls.index', ['login' => $this->user->login]),
            // Владелец стены и тот, кто оставил запись
            'user'       => AuthorResource::make($this->user),
            'author'     => AuthorResource::make($this->author),
            'created_at' => dateFixed($this->created_at, 'c', true),
        ];
    }
}
