<?php

namespace Modules\Payment\Requests;

use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Payment\Models\PaidAdvert;

class CalculateRequest extends FormRequest
{
    private ?PaidAdvert $renewed = null;

    /**
     * Продлеваемая реклама текущего пользователя, null — новая покупка
     *
     * Истёкшую тоже можно продлить. Чужая или карточка — 404,
     * гость своей рекламы не имеет
     */
    public function renewed(): ?PaidAdvert
    {
        if (! $this->filled('renew_id')) {
            return null;
        }

        if (! $this->renewed) {
            $advert = PaidAdvert::query()
                ->own()
                ->whereNotIn('place', PaidAdvert::CARD_PLACES)
                ->findOrFail((int) $this->input('renew_id'));

            $this->renewed = $advert;
        }

        return $this->renewed;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $this->renewed();

        return [
            'place'    => ['required', 'in:' . implode(',', PaidAdvert::PLACES)],
            'url'      => ['required', 'url:http,https', 'max:100'],
            'names'    => ['required', 'array', 'min:1'],
            'names.*'  => ['string', 'min:5', 'max:35', 'distinct'],
            'color'    => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'bold'     => ['sometimes', 'boolean'],
            'term'     => ['required', 'integer', 'min:1', 'max:365'],
            'comment'  => ['nullable', 'string', 'max:255'],
            'email'    => ['required', new EmailRule(), 'max:100'],
            'renew_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'place'   => __('payment::payments.paid_adverts.place'),
            'url'     => __('adverts.link'),
            'names.*' => __('payment::payments.paid_adverts.name'),
            'color'   => __('payment::payments.paid_adverts.color'),
            'bold'    => __('payment::payments.paid_adverts.bold'),
            'term'    => __('payment::payments.paid_adverts.term'),
            'comment' => __('main.comment'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('names')) {
                $error = collect($validator->errors()->get('names'))->flatten()->first();
                $validator->errors()->add('names.0', (string) $error);
            }
        });
    }
}
