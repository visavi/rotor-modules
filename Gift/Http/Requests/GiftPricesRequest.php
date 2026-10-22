<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Цены подарков в админке
 */
class GiftPricesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gifts'   => ['required', 'array'],
            'gifts.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'gifts.required' => __('gift::gifts.prices_not_transferred'),
            'gifts.array'    => __('gift::gifts.prices_not_transferred'),
        ];
    }

    public function attributes(): array
    {
        return [
            'gifts.*' => __('gift::gifts.price'),
        ];
    }
}
