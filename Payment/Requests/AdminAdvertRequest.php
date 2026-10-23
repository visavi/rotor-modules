<?php

namespace Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Payment\Models\PaidAdvert;

/**
 * Добавление и правка рекламной ссылки админом: срок задаётся датой, место меняется
 */
class AdminAdvertRequest extends FormRequest
{
    private ?PaidAdvert $advert = null;

    /**
     * Возвращает редактируемую ссылку
     */
    public function advert(): PaidAdvert
    {
        if (! $this->advert) {
            $advert = PaidAdvert::query()
                ->whereIn('place', PaidAdvert::PLACES)
                ->findOrFail((int) $this->route('id'));

            $this->advert = $advert;
        }

        return $this->advert;
    }

    /**
     * Пустые и повторные названия отбрасываются, как раньше: поля под них форма добавляет сама
     */
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('names'))) {
            $this->merge([
                'names' => array_values(array_unique(array_filter($this->input('names'), static fn ($name) => $name !== null && $name !== ''))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->isEditing()) {
            $this->advert();
        }

        return [
            'place'   => ['required', 'in:' . implode(',', PaidAdvert::PLACES)],
            'url'     => ['required', 'url:http,https', 'min:5', 'max:100'],
            'names'   => ['required', 'array', 'min:1'],
            'names.*' => ['string', 'min:5', 'max:35'],
            'color'   => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'bold'    => ['sometimes', 'boolean'],
            'term'    => ['required', 'date', 'after:now'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'place'   => __('payment::payments.paid_adverts.place'),
            'url'     => __('payment::payments.paid_adverts.link'),
            'names'   => __('payment::payments.paid_adverts.names'),
            'names.*' => __('payment::payments.paid_adverts.name'),
            'color'   => __('payment::payments.paid_adverts.color'),
            'bold'    => __('payment::payments.paid_adverts.bold'),
            'term'    => __('payment::payments.paid_adverts.term'),
            'comment' => __('main.comment'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'place.in'       => __('payment::payments.paid_adverts.place_invalid'),
            'names.required' => __('payment::payments.paid_adverts.names_count'),
            'term.after'     => __('payment::payments.paid_adverts.term_invalid'),
        ];
    }

    /**
     * Ошибку отдельного названия форма показывает под общим полем names
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(static function (Validator $validator) {
            $errors = $validator->errors();

            if (! $errors->has('names') && $errors->has('names.*')) {
                $errors->add('names', $errors->first('names.*'));
            }
        });
    }

    /**
     * Get the URL to redirect to on a validation error.
     */
    public function getRedirectUrl(): string
    {
        return $this->isEditing()
            ? '/admin/paid-adverts/edit/' . (int) $this->route('id')
            : '/admin/paid-adverts/create?place=' . urlencode((string) $this->input('place'));
    }

    /**
     * Правка существующей ссылки, а не добавление
     */
    private function isEditing(): bool
    {
        return $this->route('id') !== null;
    }
}
