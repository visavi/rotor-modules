<?php

namespace Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Models\PaidAdvert;

class MyCardRequest extends FormRequest
{
    use CardFieldsTrait;

    private ?PaidAdvert $card = null;

    /**
     * Возвращает редактируемую карточку текущего пользователя
     */
    public function card(): PaidAdvert
    {
        if (! $this->card) {
            $card = PaidAdvert::query()
                ->ownActive()
                ->whereIn('place', PaidAdvert::CARD_PLACES)
                ->findOrFail((int) $this->route('id'));

            $this->card = $card;
        }

        return $this->card;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Место и срок оплачены и не меняются
     */
    public function rules(): array
    {
        $this->card();

        return [
            ...$this->cardRules(),
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return $this->cardAttributes();
    }

    /**
     * Get the URL to redirect to on a validation error.
     */
    public function getRedirectUrl(): string
    {
        return '/payments/my/card/' . (int) $this->route('id');
    }
}
