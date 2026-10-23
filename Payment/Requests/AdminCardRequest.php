<?php

namespace Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Models\PaidAdvert;

/**
 * Добавление и правка карточки админом: место выбирается только при добавлении,
 * срок задаётся датой, лимит места не проверяется
 */
class AdminCardRequest extends FormRequest
{
    use CardFieldsTrait;

    private ?PaidAdvert $card = null;

    /**
     * Возвращает редактируемую карточку
     */
    public function card(): PaidAdvert
    {
        if (! $this->card) {
            $card = PaidAdvert::query()
                ->whereIn('place', PaidAdvert::CARD_PLACES)
                ->findOrFail((int) $this->route('id'));

            $this->card = $card;
        }

        return $this->card;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->isEditing()) {
            $this->card();

            return [
                ...$this->cardRules(),
                'remove_image' => ['sometimes', 'boolean'],
                'term'         => ['required', 'date', 'after:now'],
            ];
        }

        return [
            'place' => ['required', 'in:' . implode(',', PaidAdvert::CARD_PLACES)],
            ...$this->cardRules(),
            'term' => ['required', 'date', 'after:now'],
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
            'term' => __('payment::payments.paid_adverts.expires'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'term.after' => __('payment::payments.paid_adverts.term_invalid'),
        ];
    }

    /**
     * Get the URL to redirect to on a validation error.
     */
    public function getRedirectUrl(): string
    {
        return $this->isEditing()
            ? '/admin/paid-adverts/edit-card/' . (int) $this->route('id')
            : '/admin/paid-adverts/create-card';
    }

    /**
     * Правка существующей карточки, а не добавление
     */
    private function isEditing(): bool
    {
        return $this->route('id') !== null;
    }
}
