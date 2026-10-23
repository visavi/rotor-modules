<?php

declare(strict_types=1);

namespace Modules\Board\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Board\Models\Item;

/**
 * Права на объявления на сайте и в API: администрация правит чужое в админке
 */
class ItemPolicy
{
    /**
     * Подача объявлений может быть закрыта настройкой — кроме администрации
     */
    public function create(User $user): Response
    {
        return $user->isAdmin() || setting('boards_create')
            ? Response::allow()
            : Response::deny(__('board::boards.boards_closed'));
    }

    /**
     * Правка, снятие с публикации и удаление — только автором
     */
    public function update(User $user, Item $item): Response
    {
        return $item->user_id === $user->id
            ? Response::allow()
            : Response::deny(__('board::boards.item_not_author'));
    }
}
