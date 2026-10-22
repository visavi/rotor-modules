<?php

declare(strict_types=1);

namespace Modules\News\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Создание и правка новости в админке
 */
class NewsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:' . setting('news_title_min'), 'max:' . setting('news_title_max')],
            'text'  => ['required', 'string', new TextLengthRule((int) setting('news_text_min'), (int) setting('news_text_max'))],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validator.text'),
            'title.string'   => __('validator.text'),
            'text.required'  => __('validator.text'),
            'text.string'    => __('validator.text'),
        ];
    }
}
