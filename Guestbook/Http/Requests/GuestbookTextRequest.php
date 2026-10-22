<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Правка сообщения гостевой: автором на сайте и в API, администрацией в админке
 */
class GuestbookTextRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => GuestbookRequest::textRules(),
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
