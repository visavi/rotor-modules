<?php

declare(strict_types=1);

namespace Modules\Feedback\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\Concerns\ChecksCaptcha;
use App\Rules\EmailRule;
use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Feedback\Support\Feedback;

/**
 * Обращение через форму обратной связи
 *
 * Имя спрашивается только у гостя, email — когда без него не ответить
 */
#[RedirectToRoute('feedback.index')]
class FeedbackRequest extends FormRequest
{
    use ChecksCaptcha;

    public function rules(): array
    {
        $user = auth()->user();

        $rules = [
            'message' => ['required', 'string', new TextLengthRule(5, 50000), CommentRequest::floodRule()],
        ];

        if (! $user) {
            $rules['name'] = ['required', 'string', 'between:3,100'];
        }

        if (Feedback::asksEmail($user)) {
            $rules['email'] = ['required', 'string', new EmailRule()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'message.required' => __('validator.text'),
            'message.string'   => __('validator.text'),
            'name.*'           => __('feedback::feedback.name_short_or_long'),
            'email.required'   => __('validator.email'),
            'email.string'     => __('validator.email'),
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->checkCaptcha($validator),
        ];
    }
}
