<?php

declare(strict_types=1);

namespace Modules\Feedback\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Rules\EmailRule;
use Illuminate\Validation\Rule;
use Modules\Feedback\Support\Feedback;

class FeedbackSettingController extends ModuleSettingController
{
    protected string $view = 'feedback::admin/settings/_feedback';

    protected string $route = 'feedback.settings';

    protected function rules(): array
    {
        return [
            'sets.feedback_delivery' => ['required', Rule::in(Feedback::DELIVERIES)],
            'sets.feedback_email'    => ['nullable', 'string', new EmailRule()],
        ];
    }

    protected function messages(): array
    {
        return [
            'sets.feedback_delivery' => __('feedback::feedback.delivery_invalid'),
            'sets.feedback_email'    => __('validator.email'),
        ];
    }
}
