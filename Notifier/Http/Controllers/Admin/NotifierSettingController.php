<?php

declare(strict_types=1);

namespace Modules\Notifier\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
    public function update(Request $request): RedirectResponse
    {
        $validator = new Validator();
        $sets = (array) $request->input('sets', []);

        // При выключенной проверке остальные поля заблокированы в форме
        // и браузер их не отправляет — проверяем только пришедшее,
        // непришедшее сохраняет прежнее значение
        if (array_key_exists('notifier_interval', $sets)) {
            $validator->between(
                (int) $sets['notifier_interval'],
                Notifier::MIN_INTERVAL,
                Notifier::MAX_INTERVAL,
                ['sets[notifier_interval]' => __('notifier::notifier.interval_invalid', [
                    'min' => Notifier::MIN_INTERVAL,
                    'max' => Notifier::MAX_INTERVAL,
                ])],
            );
        }

        if (array_key_exists('notifier_volume', $sets)) {
            $validator->between((int) $sets['notifier_volume'], 0, 100, [
                'sets[notifier_volume]' => __('notifier::notifier.volume_invalid'),
            ]);
        }

        if (! empty($sets['notifier_sound'])) {
            $validator->in((string) $sets['notifier_sound'], array_keys(Notifier::sounds()), [
                'sets[notifier_sound]' => __('notifier::notifier.sound_invalid'),
            ]);
        }

        if ($validator->fails()) {
            return redirect()->route($this->route)
                ->withInput()
                ->withErrors($validator->getErrors());
        }

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
