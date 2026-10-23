<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\Concerns\ChecksCaptcha;
use App\Rules\TextLengthRule;
use App\Services\FileService;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Modules\Guestbook\Models\Guestbook;

/**
 * Новое сообщение в гостевой — общее для сайта и API
 *
 * Гостю писать можно, если это открыто настройкой: тогда нужна капча,
 * а ссылки в тексте не принимаются
 */
#[RedirectToRoute('guestbook.index')]
class GuestbookRequest extends FormRequest
{
    use ChecksCaptcha;

    public function authorize(): Response
    {
        return Gate::inspect('create', Guestbook::class);
    }

    public function rules(): array
    {
        if (auth()->check()) {
            // Вложения — только от пользователей: файл гостя открыт
            // по ссылке ещё до модерации и обходит запрет ссылок в тексте
            return [
                'text' => [...self::textRules(), CommentRequest::floodRule()],
                ...FileService::rules(Guestbook::$morphName),
            ];
        }

        return [
            'text' => [
                ...self::textRules(),
                CommentRequest::floodRule(),
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (str_contains((string) $value, '//')) {
                        $fail(__('guestbook::guestbook.without_links'));
                    }
                },
            ],
            'guest_name' => ['nullable', 'string', 'between:3,20'],
            'files'      => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('validator.text'),
            'text.string'   => __('validator.text'),
            'guest_name.*'  => __('users.name_short_or_long'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (auth()->guest()) {
                    $this->checkCaptcha($validator);
                }
            },
        ];
    }

    /**
     * Правила текста сообщения: длина из настроек гостевой, без учёта разметки
     */
    public static function textRules(): array
    {
        return [
            'required',
            'string',
            new TextLengthRule((int) setting('guestbook_text_min'), (int) setting('guestbook_text_max')),
        ];
    }
}
