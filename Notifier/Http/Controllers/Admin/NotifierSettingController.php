<?php

declare(strict_types=1);

namespace Modules\Notifier\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Notifier\Support\Notifier;

class NotifierSettingController extends ModuleSettingController
{
    protected string $view = 'notifier::admin/settings/_notifier';

    protected string $route = 'notifier.settings';

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

    protected function rules(): array
    {
        return [
            'sets.notifier_active'   => ['boolean'],
            'sets.notifier_interval' => ['sometimes', 'integer', 'between:' . Notifier::MIN_INTERVAL . ',' . Notifier::MAX_INTERVAL],
            // «Без звука» — пустое поле
            'sets.notifier_sound'   => ['nullable', Rule::in(array_keys(Notifier::sounds()))],
            'sets.notifier_volume'  => ['sometimes', 'integer', 'between:0,100'],
            'sets.notifier_title'   => ['boolean'],
            'sets.notifier_desktop' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'sets.notifier_interval' => __('notifier::notifier.interval_invalid', [
                'min' => Notifier::MIN_INTERVAL,
                'max' => Notifier::MAX_INTERVAL,
            ]),
            'sets.notifier_volume' => __('notifier::notifier.volume_invalid'),
            'sets.notifier_sound'  => __('notifier::notifier.sound_invalid'),
        ];
    }
}
