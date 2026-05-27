<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Controllers\Admin;

use App\Classes\Validator;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestbookSettingController extends AdminController
{
    /**
     * Настройки
     */
    public function index(): View
    {
        $settings = Setting::getSettings();

        return view('guestbook::admin/settings/_guestbook', compact('settings'));
    }

    /**
     * Сохранение настроек
     */
    public function update(Request $request, Validator $validator): RedirectResponse
    {
        $sets = $request->input('sets');

        $validator->notEmpty($sets, ['sets' => __('settings.settings_empty')]);

        if ($validator->isValid()) {
            foreach ($sets as $name => $value) {
                Setting::query()->updateOrCreate(['name' => $name], ['value' => $value]);
            }

            clearCache('settings');

            return redirect()
                ->route('guestbook.settings')
                ->with('success', __('settings.settings_success_saved'));
        }

        return back()
            ->withErrors($validator->getErrors())
            ->withInput();
    }
}
