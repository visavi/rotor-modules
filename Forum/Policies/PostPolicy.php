<?php

declare(strict_types=1);

namespace Modules\Forum\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Forum\Models\Post;

/**
 * Права на сообщения форума: администрация правит в админке
 */
class PostPolicy
{
    /**
     * Сколько минут после публикации автор может править сообщение
     */
    public const EDIT_MINUTES = 10;

    /**
     * Своё сообщение — первые минуты после публикации, куратору — любое; в закрытой теме — никому
     */
    public function update(User $user, Post $post): Response
    {
        $topic = $post->topic;

        return match (true) {
            (bool) $topic->closed                                        => Response::deny(__('forum::forums.topic_closed')),
            $topic->isModerator($user)                                   => Response::allow(),
            $post->user_id !== $user->id                                 => Response::deny(__('forum::forums.posts_edited_curators')),
            $post->created_at->lt(now()->subMinutes(self::EDIT_MINUTES)) => Response::deny(__('main.editing_impossible')),
            default                                                      => Response::allow(),
        };
    }
}
