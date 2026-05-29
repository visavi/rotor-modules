<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends AdminController
{
    /**
     * Настройки
     */
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('advert::admin/settings/index', compact('settings'));
    }

    /**
     * Сохранение настроек
     */
    public function update(Request $request): RedirectResponse
    {
        $sets = $request->input('sets');

        $settings = [
            'rekusershow'     => int($sets['rekusershow'] ?? 1),
            'rekuserprice'    => int($sets['rekuserprice'] ?? 1000),
            'rekuserpoint'    => int($sets['rekuserpoint'] ?? 50),
            'rekuseroptprice' => int($sets['rekuseroptprice'] ?? 100),
            'rekusertime'     => int($sets['rekusertime'] ?? 12),
            'rekusertotal'    => int($sets['rekusertotal'] ?? 10),
            'rekuserpost'     => int($sets['rekuserpost'] ?? 10),
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->where('name', $key)->update(['value' => $value]);
        }

        clearCache('settings');
        setFlash('success', __('advert::adverts.settings_saved'));

        return redirect()->route('advert.settings');
    }
}
