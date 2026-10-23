<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use App\Rules\TextLengthRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Blog\Models\Blog;

/**
 * Статья — правка на сайте, в админке и в API
 *
 * Форма сайта шлёт флажок delay и кнопку action=draft|publish,
 * API — draft, publish и published_at: здесь они сводятся к одному
 */
class ArticleRequest extends FormRequest
{
    private ?Blog $category = null;

    public function rules(): array
    {
        return [
            'category_id' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->category = Blog::query()->find(int($value));

                    if (! $this->category) {
                        $fail(__('blog::blogs.category_not_exist'));
                    } elseif ($this->category->closed) {
                        $fail(__('blog::blogs.category_closed'));
                    }
                },
            ],
            'title'   => ['required', 'string', 'min:' . setting('blog_title_min'), 'max:' . setting('blog_title_max')],
            'text'    => ['required', 'string', new TextLengthRule((int) setting('blog_text_min'), (int) setting('blog_text_max'))],
            'tags'    => ['required', 'array', 'between:1,10'],
            'tags.*'  => ['string', 'min:' . setting('blog_tag_min'), 'max:' . setting('blog_tag_max')],
            'draft'   => ['nullable', 'boolean'],
            'publish' => ['nullable', 'boolean'],
            // Отложенная публикация: отмеченный флажок без даты оставлял статью неактивной навсегда
            'published_at' => [Rule::requiredIf($this->boolean('delay')), 'nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => __('blog::blogs.category_not_exist'),
            'category_id.integer'  => __('blog::blogs.category_not_exist'),
            'title'                => __('validator.text'),
            'text.required'        => __('validator.text'),
            'text.string'          => __('validator.text'),
            'tags.*.*'             => __('blog::blogs.article_error_tags'),
            'tags'                 => __('blog::blogs.article_count_tags'),
            'published_at'         => __('blog::blogs.article_delayed_time'),
        ];
    }

    /**
     * Раздел, найденный при проверке
     */
    public function category(): Blog
    {
        return $this->category;
    }

    /**
     * Теги без повторов, в порядке ввода
     *
     * @return list<string>
     */
    public function tagNames(): array
    {
        return array_values(array_unique($this->validated('tags')));
    }

    /**
     * Дата отложенной публикации — только у администрации
     */
    public function publishedAt(): ?string
    {
        return isAdmin() && ! $this->boolean('publish') ? $this->validated('published_at') : null;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // Кнопки формы сайта
        if ($this->input('action') === 'draft') {
            $data['draft'] = true;
        }

        if ($this->input('action') === 'publish') {
            $data['publish'] = true;
        }

        // Снятый флажок прячет поле даты, но браузер её всё равно отправляет
        if ($this->has('delay') && ! $this->boolean('delay')) {
            $data['published_at'] = null;
        }

        // Пустые и повторные теги не считаются
        if (is_array($this->input('tags'))) {
            $data['tags'] = array_values(array_unique(array_filter($this->input('tags'), static fn ($tag) => $tag !== null && $tag !== '')));
        }

        $this->merge($data);
    }
}
