<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Resources;

use App\Http\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Forum\Models\Topic;

/** @mixin Topic */
class TopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'forum_id' => $this->forum_id,
            // Раздел отдаётся вложенным, когда связь загружена (сообщения темы)
            'forum' => ForumResource::make($this->whenLoaded('forum')),
            'title' => e($this->title),
            'user'  => AuthorResource::make($this->user),
            // Устарели, оставлены для старых клиентов — данные есть в user
            'login'          => $this->user->login,
            'name'           => $this->user->getName(),
            'closed'         => $this->closed,
            'locked'         => $this->locked,
            'count_posts'    => $this->count_posts,
            'visits'         => $this->visits,
            'moderators'     => $this->moderators,
            'note'           => e($this->note),
            'last_post_id'   => $this->last_post_id,
            'last_post_user' => $this->lastPost->id ? AuthorResource::make($this->lastPost->user) : null,
            // Устарели, оставлены для старых клиентов — данные есть в last_post_user
            'last_post_user_login' => $this->lastPost->user->login,
            'last_post_user_name'  => $this->lastPost->user->getName(),
            'close_user_id'        => $this->close_user_id,
            'updated_at'           => dateFixed($this->updated_at, 'c', true),
            'created_at'           => dateFixed($this->created_at, 'c', true),
        ];
    }
}
