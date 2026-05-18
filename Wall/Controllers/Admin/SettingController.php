<?php

declare(strict_types=1);

namespace Modules\Wall\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends AdminController
{
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('wall::admin/settings/index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $sets = $request->input('sets');

        $settings = [
            'wallpost' => int($sets['wallpost'] ?? 10),
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->where('name', $key)->update(['value' => $value]);
        }

        setFlash('success', __('wall::walls.settings_saved'));

        return redirect()->route('wall.settings');
    }
}
