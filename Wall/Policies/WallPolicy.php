<?php

declare(strict_types=1);

namespace Modules\Wall\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Wall\Models\Wall;

/**
 * Права на записи стены на сайте и в API
 */
class WallPolicy
{
    /**
     * Чистит стену её хозяин, чужие записи трогает только администрация
     */
    public function delete(User $user, Wall $wall): Response
    {
        return $wall->user_id === $user->id || $user->isAdmin()
            ? Response::allow()
            : Response::deny(__('main.deleted_only_admins'));
    }
}
