<?php

namespace Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'yookassa_shop_id'    => 'nullable|string|max:100',
            'yookassa_secret_key' => 'nullable|string|max:255',
            'prices.places'       => 'required|array',
            'prices.places.*'     => 'required|numeric|min:0',
            'prices.colorPrice'   => 'required|numeric|min:0',
            'prices.boldPrice'    => 'required|numeric|min:0',
            'prices.namePrice'    => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'prices.places.*'   => __('payment::payments.paid_adverts.place'),
            'prices.colorPrice' => __('payment::payments.paid_adverts.color'),
            'prices.boldPrice'  => __('payment::payments.paid_adverts.bold'),
            'prices.namePrice'  => __('payment::payments.paid_adverts.name'),
        ];
    }
}
