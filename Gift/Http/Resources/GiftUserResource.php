<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Resources;

use App\Http\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Gift\Models\GiftsUser;

/** @mixin GiftsUser */
class GiftUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->gift->name,
            'url'  => url($this->gift->path),
            'text' => $this->text,
            // Кто подарил и когда подарок пропадёт из профиля
            'sender'     => AuthorResource::make($this->sendUser),
            'created_at' => dateFixed($this->created_at, 'c', true),
            'expires_at' => dateFixed($this->deleted_at, 'c', true),
        ];
    }
}
