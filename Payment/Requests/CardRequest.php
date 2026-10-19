<?php

namespace Modules\Payment\Requests;

use App\Rules\EmailRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Services\PaymentService;

class CardRequest extends FormRequest
{
    use CardFieldsTrait;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $places = app(PaymentService::class)->getCardPlaces();

        return [
            'place' => [
                'required',
                'in:' . implode(',', array_keys($places)),
                static function (string $attribute, mixed $value, Closure $fail) use ($places) {
                    $place = $places[$value] ?? null;

                    if ($place && $place['taken'] >= $place['limit']) {
                        $fail(__('payment::payments.cards.place_full', ['date' => dateFixed($place['freeAt'])]));
                    }
                },
            ],
            ...$this->cardRules(),
            'term'  => ['required', 'integer', 'min:1', 'max:365'],
            'email' => ['required', new EmailRule(), 'max:100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'place' => __('payment::payments.cards.place'),
            ...$this->cardAttributes(),
            'term' => __('payment::payments.paid_adverts.term'),
        ];
    }

    /**
     * Get the URL to redirect to on a validation error.
     */
    public function getRedirectUrl(): string
    {
        return '/payments/card';
    }
}
