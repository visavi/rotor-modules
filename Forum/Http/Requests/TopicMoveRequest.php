<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Topic;

/**
 * Перенос темы в другой раздел
 */
class TopicMoveRequest extends FormRequest
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
                    $this->forum = Forum::query()->find((int) $value);

                    if (! $this->forum) {
                        $fail(__('forum::forums.forum_not_exist'));
                    } elseif ($this->forum->closed) {
                        $fail(__('forum::forums.forum_closed'));
                    } elseif (Topic::query()->whereKey($this->route('id'))->where('forum_id', $this->forum->id)->exists()) {
                        $fail(__('forum::forums.forum_invalid'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'forum_id.required' => __('forum::forums.forum_not_exist'),
            'forum_id.integer'  => __('forum::forums.forum_not_exist'),
        ];
    }

    public function forum(): Forum
    {
        return $this->forum ?? throw new LogicException('Доступно после проверки запроса');
    }
}
