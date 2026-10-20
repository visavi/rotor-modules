<?php

declare(strict_types=1);

namespace Modules\MailNotify\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Modules\MailNotify\Http\Requests\MailNotifySettingRequest;

class MailNotifySettingController extends ModuleSettingController
{
    protected string $view = 'mail_notify::admin/settings/_mail_notify';

    protected string $route = 'mail-notify.settings';

    /**
     * Сохранение настроек
     */
    public function store(MailNotifySettingRequest $request): RedirectResponse
    {
        $days = $request->integer('sets.sendprivatmailday');
        $packet = $request->integer('sets.sendmailpacket');

        // Пишем только свои ключи: чужие имена из формы не должны попадать в настройки
        foreach (['sendprivatmailday' => $days, 'sendmailpacket' => $packet] as $name => $value) {
            Setting::query()->updateOrCreate(['name' => $name], ['value' => (string) $value]);
        }

        clearCache('settings');

        return redirect()->route($this->route)
            ->with('success', __('settings.settings_success_saved'));
    }
}
