<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use App\Rules\CategoryParentRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Forum\Models\Forum;

/**
 * Раздел форума — создание и правка в админке
 */
class ForumCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:' . setting('forum_category_min'), 'max:' . setting('forum_category_max')],
            'description' => ['nullable', 'string', 'min:' . setting('forum_description_min'), 'max:' . setting('forum_description_max')],
            'parent'      => [
                'bail',
                'nullable',
                'integer',
                new CategoryParentRule(Forum::class, (int) $this->route('id'), 'forum::forums.forum_invalid'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title'          => __('validator.text'),
            'description'    => __('validator.text'),
            'parent.integer' => __('forum::forums.forum_invalid'),
        ];
    }
}
