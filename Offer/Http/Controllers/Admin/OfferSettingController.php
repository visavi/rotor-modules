<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferSettingController extends Controller
{
    /**
     * Настройки
     */
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('offer::admin/settings/_offers', compact('settings'));
    }

    /**
     * Сохранение настроек
     */
    public function update(Request $request): RedirectResponse
    {
        $sets = $request->input('sets', []);

        if (empty($sets)) {
            setFlash('danger', __('offer::offers.settings_empty'));

            return redirect()->back();
        }

        foreach ($sets as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
        setFlash('success', __('offer::offers.settings_success_saved'));

        return redirect()->route('offer.settings');
    }
}
