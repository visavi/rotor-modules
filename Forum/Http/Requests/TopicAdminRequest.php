<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Forum\Http\Controllers\Admin\ForumController;

/**
 * Правка темы в админке: заголовок, заметка, кураторы
 */
class TopicAdminRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'min:' . setting('forum_title_min'), 'max:' . setting('forum_title_max')],
            'note'       => ['nullable', 'string', 'min:' . setting('forum_note_min'), 'max:' . setting('forum_note_max')],
            'moderators' => [
                'bail',
                'nullable',
                'array',
                'max:' . ForumController::MAX_CURATORS,
                static function (string $attribute, mixed $value, Closure $fail): void {
                    $missing = array_diff($value, User::query()->whereIn('login', $value)->pluck('login')->all());

                    if ($missing) {
                        $fail(__('validator.user_login', ['login' => implode(', ', $missing)]));
                    }
                },
            ],
            'moderators.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title'          => __('validator.text'),
            'note'           => __('validator.text_long'),
            'moderators.max' => __('forum::forums.curators_limit', ['limit' => ForumController::MAX_CURATORS]),
        ];
    }

    /**
     * Логины кураторов в том виде, как они записаны в базе
     */
    public function curators(): string
    {
        return User::query()->whereIn('login', $this->validated('moderators') ?? [])->pluck('login')->implode(',');
    }

    protected function prepareForValidation(): void
    {
        $moderators = $this->input('moderators');

        if (is_array($moderators)) {
            $this->merge(['moderators' => array_values(array_filter($moderators, static fn ($login) => is_string($login) && $login !== ''))]);
        }
    }
}
