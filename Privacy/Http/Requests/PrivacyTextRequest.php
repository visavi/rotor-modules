<?php

declare(strict_types=1);

namespace Modules\Privacy\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Новая версия текста согласия
 */
class PrivacyTextRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', new TextLengthRule(1, 65000)],
        ];
    }
}
