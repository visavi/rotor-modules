<?php

declare(strict_types=1);

namespace Modules\Guestbook\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Guestbook\Models\Guestbook;

/**
 * Права на сообщения гостевой на сайте и в API: администрация правит чужое в админке
 */
class GuestbookPolicy
{
    /**
     * Сколько минут после публикации автор может править сообщение
     */
    public const EDIT_MINUTES = 10;

    /**
     * Гостю писать можно, только если гостевая открыта настройкой
     */
    public function create(?User $user): Response
    {
        return $user || setting('bookadds')
            ? Response::allow()
            : Response::deny(__('main.not_authorized'));
    }

    public function update(User $user, Guestbook $post): Response
    {
        return match (true) {
            $post->user_id !== $user->id                                 => Response::deny(__('ajax.record_not_author')),
            $post->created_at->lt(now()->subMinutes(self::EDIT_MINUTES)) => Response::deny(__('main.editing_impossible')),
            default                                                      => Response::allow(),
        };
    }
}
