<?php

namespace Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Models\PaidAdvert;

class MyAdvertRequest extends FormRequest
{
    private ?PaidAdvert $advert = null;

    /**
     * Возвращает редактируемую рекламу текущего пользователя
     */
    public function advert(): PaidAdvert
    {
        if (! $this->advert) {
            $advert = PaidAdvert::query()->ownActive()->find((int) $this->route('id'));

            if (! $advert) {
                abort(404, __('payment::payments.paid_adverts.not_found'));
            }

            $this->advert = $advert;
        }

        return $this->advert;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Оплаченные опции заблокированы: нельзя добавить названия сверх оплаченных,
     * цвет валидируется только если был оплачен
     */
    public function rules(): array
    {
        $advert = $this->advert();

        $rules = [
            'site'    => ['required', 'url', 'max:100'],
            'names'   => ['required', 'array', 'min:1', 'max:' . count($advert->names)],
            'names.*' => ['string', 'min:5', 'max:35', 'distinct'],
        ];

        if ($advert->color) {
            $rules['color'] = ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'site'    => __('payment::payments.paid_adverts.link'),
            'names.*' => __('payment::payments.paid_adverts.name'),
            'color'   => __('payment::payments.paid_adverts.color'),
        ];
    }
}
