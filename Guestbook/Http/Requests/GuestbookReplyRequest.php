<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ответ администрации на сообщение гостевой
 */
class GuestbookReplyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reply' => GuestbookRequest::textRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'reply.required' => __('validator.text'),
            'reply.string'   => __('validator.text'),
        ];
    }
}
