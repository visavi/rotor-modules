<?php

declare(strict_types=1);

namespace Modules\AdminChat\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\AdminChat\Models\Chat;

/**
 * Права на сообщения админ-чата
 */
class ChatPolicy
{
    /**
     * Сколько минут после публикации автор может править сообщение
     */
    public const EDIT_MINUTES = 10;

    public function update(User $user, Chat $post): Response
    {
        return match (true) {
            $post->user_id !== $user->id                                 => Response::deny(__('ajax.record_not_author')),
            $post->created_at->lt(now()->subMinutes(self::EDIT_MINUTES)) => Response::deny(__('main.editing_impossible')),
            default                                                      => Response::allow(),
        };
    }
}
