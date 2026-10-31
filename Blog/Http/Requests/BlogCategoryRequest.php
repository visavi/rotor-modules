<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use App\Rules\CategoryParentRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Blog\Models\Blog;

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
                'bail',
                'nullable',
                'integer',
                new CategoryParentRule(Blog::class, (int) $this->route('id'), 'blog::blogs.category_not_exist'),
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
