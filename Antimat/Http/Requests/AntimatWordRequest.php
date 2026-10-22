<?php

declare(strict_types=1);

namespace Modules\Antimat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Добавление слова в список антимата
 */
class AntimatWordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Колонка string(100)
            'word' => ['required', 'string', 'max:100', 'unique:antimat,string'],
        ];
    }

    public function messages(): array
    {
        return [
            'word.required' => __('antimat::antimat.not_enter_word'),
            'word.unique'   => __('antimat::antimat.word_listed'),
        ];
    }

    /**
     * Список хранится в нижнем регистре: так же ищется и повтор
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['word' => Str::lower($this->string('word')->value())]);
    }
}
