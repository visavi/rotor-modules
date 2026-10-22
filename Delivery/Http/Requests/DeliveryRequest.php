<?php

declare(strict_types=1);

namespace Modules\Delivery\Http\Requests;

use App\Http\Requests\CommentRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Приватная рассылка: текст и группа получателей
 */
class DeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => CommentRequest::textRules(),
            'type' => ['required', 'integer', 'between:1,4'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('validator.text'),
            'text.string'   => __('validator.text'),
            'type'          => __('delivery::delivery.not_recipients_selected'),
        ];
    }
}
