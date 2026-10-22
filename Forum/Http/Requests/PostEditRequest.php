<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Правка сообщения — на сайте и в админке
 */
class PostEditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => PostRequest::textRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('validator.text'),
            'text.string'   => __('validator.text'),
        ];
    }
}
