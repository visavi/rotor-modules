<?php

declare(strict_types=1);

namespace Modules\Photo\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Photo\Models\Photo;

/**
 * Права на фотографии на сайте и в API: администрация правит чужое в админке
 */
class PhotoPolicy
{
    /**
     * Загрузка может быть закрыта настройкой — кроме администрации
     */
    public function create(User $user): Response
    {
        return $user->isAdmin() || setting('photos_create')
            ? Response::allow()
            : Response::deny(__('photo::photos.photos_closed'));
    }

    /**
     * Правка и удаление — только автором
     */
    public function update(User $user, Photo $photo): Response
    {
        return $photo->user_id === $user->id
            ? Response::allow()
            : Response::deny(__('photo::photos.photo_not_author'));
    }
}
