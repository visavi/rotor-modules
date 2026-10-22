<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;

/**
 * Правка своей темы: заголовок, первое сообщение и опрос
 */
class TopicEditRequest extends FormRequest
{
    private ?Post $firstPost = null;

    private ?Vote $vote = null;

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'min:' . setting('forum_title_min'), 'max:' . setting('forum_title_max')],
        ];

        // Первое сообщение могли удалить модераторы — тогда поля текста в форме нет
        if ($this->firstPost) {
            $rules['text'] = PostRequest::textRules();
        }

        // Вопрос правится всегда, варианты — пока за опрос никто не голосовал
        if ($this->vote) {
            $rules += TopicRequest::voteRules('required');
            $rules['answers'] = ['nullable', 'array', 'between:2,10'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return TopicRequest::voteMessages() + [
            'title'         => __('validator.text'),
            'text.required' => __('validator.text'),
            'text.string'   => __('validator.text'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->vote && $this->filled('answers') && $this->vote->count) {
                    $validator->errors()->add('question', __('forum::forums.vote_answer_changed_impossible'));
                }
            },
        ];
    }

    /**
     * Первое сообщение темы, если оно есть
     */
    public function firstPost(): ?Post
    {
        return $this->firstPost;
    }

    /**
     * Опрос темы, если он есть
     */
    public function vote(): ?Vote
    {
        return $this->vote;
    }

    protected function prepareForValidation(): void
    {
        if (! Topic::query()->whereKey($this->route('id'))->exists()) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $this->firstPost = Post::query()->where('topic_id', $this->route('id'))->orderBy('id')->first();
        $this->vote = Vote::query()->where('topic_id', $this->route('id'))->first();

        $this->merge(TopicRequest::cleanAnswers($this));
    }
}
