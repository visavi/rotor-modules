<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsSettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('news::admin/settings/_news', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $sets = $request->input('sets', []);

        if (empty($sets)) {
            setFlash('danger', __('news::news.settings_empty'));

            return redirect()->back();
        }

        foreach ($sets as $name => $value) {
            Setting::query()->updateOrInsert(['name' => $name], ['value' => $value]);
        }

        clearCache('settings');
        setFlash('success', __('news::news.settings_success_saved'));

        return redirect()->route('news.settings');
    }
}
