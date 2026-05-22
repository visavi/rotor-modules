<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhotoSettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('photo::admin/settings/_photos', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $sets = $request->input('sets', []);

        if (empty($sets)) {
            setFlash('danger', __('photo::photos.settings_empty'));

            return redirect()->back();
        }

        foreach ($sets as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
        setFlash('success', __('photo::photos.settings_success_saved'));

        return redirect()->route('photo.settings');
    }
}
