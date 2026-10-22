<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Рекламная ссылка — покупка на сайте, правка и своя ссылка в админке
 */
class AdvertRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'site'  => ['required', 'string', 'between:5,100', 'regex:~^https?://[а-яёa-z0-9_\-.]+(\.[а-яёa-z0-9/\-?_=#]+)+$~iu'],
            'name'  => ['required', 'string', 'between:5,35'],
            'color' => ['nullable', 'regex:/^#[a-f0-9]{6}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'site.regex' => __('validator.url'),
            'site.*'     => __('validator.url_text'),
            'name.*'     => __('validator.text'),
            'color'      => __('validator.color'),
        ];
    }

    /**
     * Поля ссылки из запроса
     */
    public function advertFields(): array
    {
        return [
            'site'  => $this->string('site')->value(),
            'name'  => $this->string('name')->value(),
            'color' => $this->input('color'),
            'bold'  => $this->boolean('bold'),
        ];
    }
}
