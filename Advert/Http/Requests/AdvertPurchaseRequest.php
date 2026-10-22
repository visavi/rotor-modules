<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Requests;

use App\Http\Requests\Concerns\ChecksCaptcha;
use Illuminate\Validation\Validator;

/**
 * Покупка рекламы пользователем
 */
class AdvertPurchaseRequest extends AdvertRequest
{
    use ChecksCaptcha;

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->checkCaptcha($validator);

                if (getUser('money') < $this->price()) {
                    $validator->errors()->add('money', __('advert::adverts.advert_not_money'));
                }
            },
        ];
    }

    /**
     * Цена ссылки: каждая опция оформления оплачивается отдельно
     */
    public function price(): int
    {
        $price = (int) setting('rekuserprice');

        if ($this->filled('color')) {
            $price += (int) setting('rekuseroptprice');
        }

        if ($this->boolean('bold')) {
            $price += (int) setting('rekuseroptprice');
        }

        return $price;
    }
}
