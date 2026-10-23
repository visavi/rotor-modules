<?php

declare(strict_types=1);

namespace Modules\Offer\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Offer\Models\Offer;

/**
 * Права на предложения и проблемы на сайте и в API: администрация отвечает в админке
 */
class OfferPolicy
{
    /**
     * Автор правит запись, пока она не решена
     */
    public function update(User $user, Offer $offer): Response
    {
        return match (true) {
            $offer->user_id !== $user->id                                   => Response::deny(__('ajax.record_not_author')),
            ! in_array($offer->status, [Offer::WAIT, Offer::PROCESS], true) => Response::deny(__('offer::offers.already_resolved')),
            default                                                         => Response::allow(),
        };
    }
}
