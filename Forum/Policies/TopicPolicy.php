<?php

declare(strict_types=1);

namespace Modules\Forum\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Forum\Models\Topic;

/**
 * Права автора и кураторов темы: администрация правит в админке
 */
class TopicPolicy
{
    /**
     * Править и закрывать свою открытую тему, если хватает баллов
     */
    public function update(User $user, Topic $topic): Response
    {
        return match (true) {
            $user->point < setting('editforumpoint') => Response::deny(__('forum::forums.topic_edited_points', ['point' => plural(setting('editforumpoint'), setting('scorename'))])),
            $topic->user_id !== $user->id            => Response::deny(__('forum::forums.topic_not_author')),
            (bool) $topic->closed                    => Response::deny(__('forum::forums.topic_closed')),
            default                                  => Response::allow(),
        };
    }

    /**
     * Открыть тему может автор, если сам её и закрыл
     */
    public function open(User $user, Topic $topic): Response
    {
        return match (true) {
            $topic->user_id !== $user->id       => Response::deny(__('forum::forums.topic_not_author')),
            $topic->close_user_id !== $user->id => Response::deny(__('forum::forums.topic_opened_author')),
            ! $topic->closed                    => Response::deny(__('forum::forums.topic_already_open')),
            default                             => Response::allow(),
        };
    }

    /**
     * Куратор открытой темы правит и удаляет любые сообщения в ней
     */
    public function moderate(User $user, Topic $topic): Response
    {
        return match (true) {
            (bool) $topic->closed        => Response::deny(__('forum::forums.topic_closed')),
            ! $topic->isModerator($user) => Response::deny(__('forum::forums.posts_deleted_curators')),
            default                      => Response::allow(),
        };
    }
}
