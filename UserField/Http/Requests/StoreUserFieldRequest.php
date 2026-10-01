<?php

namespace Modules\UserField\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\UserField\Models\UserField;

class StoreUserFieldRequest extends FormRequest
{
    /**
     * Мин. и макс. нужны только текстовым типам и числу: у остальных форма их прячет и не отправляет
     */
    protected function prepareForValidation(): void
    {
        if (! in_array($this->input('type'), UserField::LIMIT_TYPES, true)) {
            $this->merge([
                'min' => $this->input('min') ?? 0,
                'max' => $this->input('max') ?? 0,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type'        => 'required|in:' . implode(',', UserField::TYPES),
            'name'        => 'required|max:50',
            'placeholder' => 'nullable|max:100',
            'hint'        => 'nullable|max:255',
            // Выбор из одного варианта — это переключатель, а не список
            'options' => [
                'required_if:type,' . implode(',', UserField::OPTION_TYPES),
                'nullable',
                'max:5000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (in_array($this->input('type'), UserField::OPTION_TYPES, true) && count(UserField::parseOptions($value)) < 2) {
                        $fail(__('user_field::user_fields.options_min'));
                    }
                },
            ],
            'min'      => 'required|integer',
            'max'      => 'required|integer',
            'required' => 'boolean',
        ];
    }
}
