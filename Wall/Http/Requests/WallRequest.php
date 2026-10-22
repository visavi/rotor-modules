<?php

declare(strict_types=1);

namespace Modules\Wall\Http\Requests;

use App\Http\Requests\CommentRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запись на стену — общая для сайта и API
 *
 * Длина и антифлуд те же, что у комментариев: стена берёт их настройки
 */
class WallRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => [...CommentRequest::textRules(), CommentRequest::floodRule()],
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
