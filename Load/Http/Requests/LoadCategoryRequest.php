<?php

declare(strict_types=1);

namespace Modules\Load\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

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
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Раздел не может быть родителем самому себе
                    if ($this->route('id') && (int) $value === (int) $this->route('id')) {
                        $fail(__('load::loads.load_parent_invalid'));
                    }
                },
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
