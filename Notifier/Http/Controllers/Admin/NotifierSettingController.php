<?php

declare(strict_types=1);

namespace Modules\Notifier\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Notifier\Http\Requests\NotifierSettingRequest;
use Modules\Notifier\Support\Notifier;

class NotifierSettingController extends ModuleSettingController
{
    protected string $view = 'notifier::admin/settings/_notifier';

    protected string $route = 'notifier.settings';

    /**
     * Настройки, которыми управляет эта страница
     */
    private const array SETTINGS = [
        'notifier_active',
        'notifier_interval',
        'notifier_sound',
        'notifier_volume',
        'notifier_title',
        'notifier_desktop',
    ];

    /**
     * Настройки
     */
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();
        $sounds = Notifier::sounds();
        $intervals = Notifier::INTERVALS;

        return view($this->view, compact('settings', 'sounds', 'intervals'));
    }

    /**
     * Сохранение настроек
     */
    public function store(NotifierSettingRequest $request): RedirectResponse
    {
        $sets = (array) $request->input('sets', []);

        // Пишем только свои ключи: чужие имена из формы не должны попадать в настройки
        foreach (array_intersect_key($sets, array_flip(self::SETTINGS)) as $name => $value) {
            // «Без звука» — пустое поле, оно приходит null, а колонка value NOT NULL
            Setting::query()->updateOrCreate(['name' => $name], ['value' => (string) $value]);
        }

        clearCache('settings');

        return redirect()->route($this->route)
            ->with('success', __('settings.settings_success_saved'));
    }
}
