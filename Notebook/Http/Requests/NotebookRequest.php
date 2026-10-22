<?php

declare(strict_types=1);

namespace Modules\Notebook\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Сохранение заметки — общее для сайта и API
 *
 * Пустая заметка допустима: так её очищают
 */
class NotebookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', new TextLengthRule(0, 10000)],
        ];
    }
}
