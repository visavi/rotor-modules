<?php

namespace Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            '_token'            => ['required', 'in:' . csrf_token()],
            'prices.places'     => 'required|array',
            'prices.places.*'   => 'required|numeric|min:0',
            'prices.colorPrice' => 'required|numeric|min:0',
            'prices.boldPrice'  => 'required|numeric|min:0',
            'prices.namePrice'  => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'prices.places.*'   => __('admin.paid_adverts.place'),
            'prices.colorPrice' => __('admin.paid_adverts.color'),
            'prices.boldPrice'  => __('admin.paid_adverts.bold'),
            'prices.namePrice'  => __('admin.paid_adverts.name'),
        ];
    }
}
