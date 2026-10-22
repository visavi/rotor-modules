<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Раздел блогов — создание и правка в админке
 */
class BlogCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'min:' . setting('blog_category_min'), 'max:' . setting('blog_category_max')],
            'parent' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Раздел не может быть родителем самому себе
                    if ($this->route('id') && (int) $value === (int) $this->route('id')) {
                        $fail(__('blog::blogs.category_not_exist'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name'           => __('validator.text'),
            'parent.integer' => __('blog::blogs.category_not_exist'),
        ];
    }
}
