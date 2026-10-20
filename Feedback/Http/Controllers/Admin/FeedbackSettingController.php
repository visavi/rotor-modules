<?php

declare(strict_types=1);

namespace Modules\Feedback\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Modules\Feedback\Http\Requests\FeedbackSettingRequest;

class FeedbackSettingController extends ModuleSettingController
{
    protected string $view = 'feedback::admin/settings/_feedback';

    protected string $route = 'feedback.settings';

    /**
     * Сохранение настроек
     */
    public function store(FeedbackSettingRequest $request): RedirectResponse
    {
        $delivery = $request->string('sets.feedback_delivery')->value();
        $email = $request->string('sets.feedback_email')->trim()->value();

        // Пишем только свои ключи: чужие имена из формы не должны попадать в настройки
        foreach (['feedback_delivery' => $delivery, 'feedback_email' => $email] as $name => $value) {
            Setting::query()->updateOrCreate(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');

        return redirect()->route($this->route)
            ->with('success', __('settings.settings_success_saved'));
    }
}
