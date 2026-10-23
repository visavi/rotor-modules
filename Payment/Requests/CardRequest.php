<?php

namespace Modules\Payment\Requests;

use App\Rules\EmailRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Services\PaymentService;

class CardRequest extends FormRequest
{
    use CardFieldsTrait;

    private ?PaidAdvert $renewed = null;

    /**
     * Продлеваемая карточка текущего пользователя, null — новая покупка
     *
     * Истёкшую тоже можно продлить, чужая — 404
     */
    public function renewed(): ?PaidAdvert
    {
        if (! $this->filled('renew_id')) {
            return null;
        }

        if (! $this->renewed) {
            $card = PaidAdvert::query()
                ->own()
                ->whereIn('place', PaidAdvert::CARD_PLACES)
                ->findOrFail((int) $this->input('renew_id'));

            $this->renewed = $card;
        }

        return $this->renewed;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Продлеваемая карточка своё место не занимает
     */
    public function rules(): array
    {
        $places = app(PaymentService::class)->getCardPlaces($this->renewed()?->id);

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

            'renew_id'     => ['nullable', 'integer'],
            'remove_image' => ['sometimes', 'boolean'],
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
        $card = $this->renewed();

        return $card ? '/payments/my/card/renew/' . $card->id : '/payments/card';
    }
}
