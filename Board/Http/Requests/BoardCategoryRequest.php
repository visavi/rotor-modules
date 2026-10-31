<?php

declare(strict_types=1);

namespace Modules\Board\Http\Requests;

use App\Rules\CategoryParentRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Board\Models\Board;

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
                'bail',
                'nullable',
                'integer',
                new CategoryParentRule(Board::class, (int) $this->route('id'), 'board::boards.category_parent_invalid'),
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
