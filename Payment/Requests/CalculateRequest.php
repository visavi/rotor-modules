<?php

namespace Modules\Payment\Requests;

use App\Models\PaidAdvert;
use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;

class CalculateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'place'   => ['required', 'in:' . implode(',', PaidAdvert::PLACES)],
            'site'    => ['required', 'url', 'max:100'],
            'names'   => ['required', 'array', 'min:1'],
            'names.*' => ['string', 'min:5', 'max:35', 'distinct'],
            'color'   => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'bold'    => ['sometimes', 'boolean'],
            'term'    => ['required', 'integer', 'min:1', 'max:365'],
            'comment' => ['nullable', 'string', 'max:255'],
            'email'   => ['required', new EmailRule(), 'max:100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'place'   => __('admin.paid_adverts.place'),
            'site'    => __('adverts.link'),
            'names.*' => __('admin.paid_adverts.name'),
            'color'   => __('admin.paid_adverts.color'),
            'bold'    => __('admin.paid_adverts.bold'),
            'term'    => __('admin.paid_adverts.term'),
            'comment' => __('main.comment'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('names')) {
                $errors = $validator->errors()->get('names');
                $error = collect($errors)->flatten()->first();
                $validator->errors()->add('names.0', $error);
            }
        });
    }
}
