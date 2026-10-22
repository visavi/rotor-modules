<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Rules\TextLengthRule;
use App\Services\FileService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;

/**
 * Новое сообщение в теме — общее для сайта и API
 */
class PostRequest extends FormRequest
{
    private Topic $topic;

    private ?Post $lastPost;

    public function rules(): array
    {
        return [
            'text' => [
                ...self::textRules(),
                CommentRequest::floodRule(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->topic->closed) {
                        $fail(__('forum::forums.topic_closed'));
                    } elseif ($this->lastPost && $this->lastPost->getRawOriginal('text') === $value) {
                        // Повтор последнего сообщения — скорее всего, двойная отправка.
                        // Сравнивается текст из базы: атрибут уже прошёл фильтры вывода и стикеры
                        $fail(__('forum::forums.post_repeat'));
                    }
                },
            ],
            ...FileService::rules(Post::$morphName),
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('validator.text'),
            'text.string'   => __('validator.text'),
        ];
    }

    /**
     * Правила текста сообщения: длина из настроек форума, без учёта разметки
     */
    public static function textRules(): array
    {
        return ['required', 'string', new TextLengthRule((int) setting('forum_text_min'), (int) setting('forum_text_max'))];
    }

    public function topic(): Topic
    {
        return $this->topic;
    }

    /**
     * Последнее сообщение темы: с ним склеивается новое
     */
    public function lastPost(): ?Post
    {
        return $this->lastPost;
    }

    /**
     * Ошибка возвращает на последнюю страницу темы, к форме ответа
     */
    protected function getRedirectUrl(): string
    {
        return route('topics.topic', ['id' => $this->topic->id, 'page' => Topic::pageOf($this->topic->count_posts)]);
    }

    protected function prepareForValidation(): void
    {
        $topic = Topic::query()
            ->where('topics.id', $this->route('id'))
            ->with('forum.parent')
            ->first();

        if (! $topic) {
            abort(404, __('forum::forums.topic_not_exist'));
        }

        $this->topic = $topic;
        $this->lastPost = Post::query()->where('topic_id', $topic->id)->orderByDesc('id')->first();
    }
}
