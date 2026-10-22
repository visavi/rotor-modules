<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Services\FileService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;

/**
 * Новая тема — общая для сайта и API
 *
 * Раздел на сайте выбирается в форме, в API — адресом. Опрос на сайте
 * включает флажок vote, в API его включает сам вопрос
 */
class TopicRequest extends FormRequest
{
    private ?Forum $forum = null;

    public function rules(): array
    {
        return [
            'forum_id' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->forum = Forum::query()->find($value);

                    if (! $this->forum) {
                        $fail(__('forum::forums.forum_not_exist'));
                    } elseif ($this->forum->closed) {
                        $fail(__('forum::forums.forum_closed'));
                    }
                },
            ],
            'title' => [
                'required',
                'string',
                'min:' . setting('forum_title_min'),
                'max:' . setting('forum_title_max'),
                CommentRequest::floodRule(),
            ],
            'text' => PostRequest::textRules(),
            ...self::voteRules(Rule::requiredIf($this->boolean('vote'))),
            ...FileService::rules(Post::$morphName),
        ];
    }

    public function messages(): array
    {
        return self::voteMessages() + [
            'forum_id.required' => __('forum::forums.forum_not_exist'),
            'forum_id.integer'  => __('forum::forums.forum_not_exist'),
            'title.required'    => __('validator.text'),
            'title.string'      => __('validator.text'),
            'title.min'         => __('validator.text'),
            'title.max'         => __('validator.text'),
            'text.required'     => __('validator.text'),
            'text.string'       => __('validator.text'),
        ];
    }

    /**
     * Правила опроса: вопрос и от 2 до 10 вариантов
     */
    public static function voteRules(mixed $questionRequired): array
    {
        return [
            'question'  => [$questionRequired, 'nullable', 'string', 'min:' . setting('vote_title_min'), 'max:' . setting('vote_title_max')],
            'answers'   => ['required_with:question', 'nullable', 'array', 'between:2,10'],
            'answers.*' => ['string', 'min:' . setting('vote_answer_min'), 'max:' . setting('vote_answer_max')],
        ];
    }

    public static function voteMessages(): array
    {
        return [
            'question'    => __('validator.text'),
            'answers.*.*' => __('forum::forums.vote_answer_wrong_length'),
            'answers'     => __('forum::forums.vote_answer_not_enough'),
        ];
    }

    /**
     * Раздел, найденный при проверке
     */
    public function forum(): Forum
    {
        return $this->forum;
    }

    /**
     * Варианты ответа опроса, пусто — темы без опроса
     *
     * @return list<string>
     */
    public function answers(): array
    {
        return $this->filled('question') ? array_values($this->validated('answers') ?? []) : [];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // API: раздел в адресе, сайт: в форме
        if ($this->route('id')) {
            if (! Forum::query()->whereKey($this->route('id'))->exists()) {
                abort(404, __('forum::forums.forum_not_exist'));
            }

            $data['forum_id'] = (int) $this->route('id');
        }

        // Снятый флажок прячет опрос, но поля формы всё равно приходят
        if ($this->has('vote') && ! $this->boolean('vote')) {
            $data['question'] = null;
            $data['answers'] = null;
        }

        $this->merge($data + self::cleanAnswers($this));
    }

    /**
     * Пустые и повторные варианты ответа не считаются, ключи сохраняются
     */
    public static function cleanAnswers(FormRequest $request): array
    {
        $answers = $request->input('answers');

        if (! is_array($answers)) {
            return [];
        }

        return ['answers' => array_unique(array_filter($answers, static fn ($answer) => is_string($answer) && $answer !== ''))];
    }
}
