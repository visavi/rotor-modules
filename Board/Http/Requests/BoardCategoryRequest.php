<?php

declare(strict_types=1);

namespace Modules\Board\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Категория объявлений — создание и правка в админке
 */
class BoardCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'min:' . setting('board_category_min'), 'max:' . setting('board_category_max')],
            'parent' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Категория не может быть родителем самой себе
                    if ($this->route('id') && (int) $value === (int) $this->route('id')) {
                        $fail(__('board::boards.category_parent_invalid'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => __('validator.text'),
            'name.string'    => __('validator.text'),
            'parent.integer' => __('board::boards.category_parent_invalid'),
        ];
    }
}
