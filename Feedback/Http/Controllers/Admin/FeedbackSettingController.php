<?php

declare(strict_types=1);

namespace Modules\Feedback\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Feedback\Support\Feedback;

class FeedbackSettingController extends ModuleSettingController
{
    protected string $view = 'feedback::admin/settings/_feedback';

    protected string $route = 'feedback.settings';

    /**
     * Сохранение настроек
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = new Validator();
        $delivery = $request->string('sets.feedback_delivery')->value();
        $email = $request->string('sets.feedback_email')->trim()->value();

        $validator->in($delivery, Feedback::DELIVERIES, ['sets[feedback_delivery]' => __('feedback::feedback.delivery_invalid')]);

        if ($email !== '') {
            $validator->email($email, ['sets[feedback_email]' => __('validator.email')]);
        }

        if ($validator->fails()) {
            return redirect()->route($this->route)
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        // Пишем только свои ключи: чужие имена из формы не должны попадать в настройки
        foreach (['feedback_delivery' => $delivery, 'feedback_email' => $email] as $name => $value) {
            Setting::query()->updateOrCreate(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');

        return redirect()->route($this->route)
            ->with('success', __('settings.settings_success_saved'));
    }
}
