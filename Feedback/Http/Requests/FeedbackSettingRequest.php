<?php

declare(strict_types=1);

namespace Modules\Feedback\Http\Requests;

use App\Rules\EmailRule;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Feedback\Support\Feedback;

/**
 * Настройки обратной связи
 */
#[RedirectToRoute('feedback.settings')]
class FeedbackSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sets.feedback_delivery' => ['required', Rule::in(Feedback::DELIVERIES)],
            'sets.feedback_email'    => ['nullable', 'string', new EmailRule()],
        ];
    }

    public function messages(): array
    {
        return [
            'sets.feedback_delivery' => __('feedback::feedback.delivery_invalid'),
            'sets.feedback_email'    => __('validator.email'),
        ];
    }
}
