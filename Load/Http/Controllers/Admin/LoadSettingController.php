<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoadSettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('load::admin/settings/_loads', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $sets = $request->input('sets', []);

        if (empty($sets)) {
            setFlash('danger', __('load::loads.settings_empty'));

            return redirect()->back();
        }

        foreach ($sets as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
        setFlash('success', __('load::loads.settings_success_saved'));

        return redirect()->route('load.settings');
    }
}
