<?php

declare(strict_types=1);

namespace Modules\AdminChat\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Сообщение в админ-чате: добавление и правка
 */
class ChatRequest extends FormRequest
{
    /**
     * Наибольшая длина сообщения, по ней же склеиваются подряд идущие
     */
    public const int MAX_LENGTH = 1500;

    public function rules(): array
    {
        return [
            'msg' => ['required', 'string', new TextLengthRule(5, self::MAX_LENGTH)],
        ];
    }

    public function messages(): array
    {
        return [
            'msg.required' => __('validator.text'),
            'msg.string'   => __('validator.text'),
        ];
    }
}
