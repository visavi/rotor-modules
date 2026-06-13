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
     * Оплаченные опции неизменяемы: количество названий фиксировано,
     * оплаченный цвет обязателен — его можно менять, но не отключать
     */
    public function rules(): array
    {
        $advert = $this->advert();

        $rules = [
            'site'    => ['required', 'url', 'max:100'],
            'names'   => ['required', 'array', 'size:' . count($advert->names)],
            'names.*' => ['required', 'string', 'min:5', 'max:35', 'distinct'],
        ];

        if ($advert->color) {
            $rules['color'] = ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'];
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
