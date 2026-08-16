<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guestbook\Models\Guestbook;

/** @mixin Guestbook */
class GuestbookResource extends JsonResource
{
    use ResolvesAttachments;

    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'text' => absolutizeUrls($this->text),
            'url'  => route('guestbook.index'),
            // Сообщения оставляют и гости — тогда вместо автора приходит только имя
            'user'       => $this->user_id ? AuthorResource::make($this->user) : null,
            'guest_name' => $this->guest_name,
            'media'      => FileResource::collection($this->resolveMedia($this->resource)),
            'files'      => FileResource::collection($this->resolveFiles($this->resource)),
            // Ответ администрации, если он уже дан
            'reply'      => $this->reply ? absolutizeUrls($this->reply) : null,
            'edited_by'  => $this->edit_user_id ? AuthorResource::make($this->editUser) : null,
            'created_at' => dateFixed($this->created_at, 'c', true),
            'updated_at' => dateFixed($this->updated_at, 'c', true),
        ];
    }
}
