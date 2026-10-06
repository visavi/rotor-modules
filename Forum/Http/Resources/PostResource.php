<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Forum\Models\Post;

/** @mixin Post */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Тема — в общих списках сообщений, внутри темы она известна клиенту
            'topic' => $this->whenLoaded('topic', fn () => [
                'id'       => $this->topic->id,
                'forum_id' => $this->topic->forum_id,
                'title'    => e($this->topic->title),
            ]),
            'user' => AuthorResource::make($this->user),
            // Устарели, оставлены для старых клиентов — данные есть в user
            'login'  => $this->user->login,
            'name'   => $this->user->getName(),
            'text'   => absolutizeUrls($this->text),
            'rating' => $this->rating,
            'vote'   => [
                'type'  => Post::$morphName,
                'id'    => $this->id,
                'value' => $this->getAttribute('vote'),
                'own'   => $this->user_id === getUser('id'),
            ],
            'files'      => FileResource::collection($this->files),
            'updated_at' => dateFixed($this->updated_at, 'c', true),
            'created_at' => dateFixed($this->created_at, 'c', true),
        ];
    }
}
