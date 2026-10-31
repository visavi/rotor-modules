<?php

declare(strict_types=1);

namespace Modules\Load\Http\Requests;

use App\Rules\CategoryParentRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Load\Models\Load;

/**
 * Раздел загрузок — создание и правка в админке
 */
class LoadCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'min:' . setting('down_category_min'), 'max:' . setting('down_category_max')],
            'parent' => [
                'bail',
                'nullable',
                'integer',
                new CategoryParentRule(Load::class, (int) $this->route('id'), 'load::loads.load_parent_invalid'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name'           => __('validator.text'),
            'parent.integer' => __('load::loads.load_parent_invalid'),
        ];
    }
}
