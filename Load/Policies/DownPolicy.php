<?php

declare(strict_types=1);

namespace Modules\Load\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Load\Models\Down;

/**
 * Права на загрузки на сайте и в API: администрация правит чужое в админке
 */
class DownPolicy
{
    /**
     * Непроверенная загрузка — со страницей и файлами — открыта только автору и администрации
     */
    public function view(?User $user, Down $down): Response
    {
        return $down->active || $user?->isAdmin(User::ADMIN) || ($user && $down->user_id === $user->id)
            ? Response::allow()
            : Response::deny(__('load::loads.down_not_verified'));
    }

    /**
     * Приём загрузок может быть закрыт настройкой — кроме администрации
     */
    public function create(User $user): Response
    {
        return $user->isAdmin() || setting('downupload')
            ? Response::allow()
            : Response::deny(__('load::loads.down_closed'));
    }

    /**
     * Автор правит свою загрузку, пока её не проверил модератор
     */
    public function update(User $user, Down $down): Response
    {
        return match (true) {
            $down->user_id !== $user->id => Response::deny(__('ajax.record_not_author')),
            (bool) $down->active         => Response::deny(__('load::loads.down_verified')),
            default                      => Response::allow(),
        };
    }
}
