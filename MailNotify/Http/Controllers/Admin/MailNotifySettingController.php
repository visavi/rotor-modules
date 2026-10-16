<?php

declare(strict_types=1);

namespace Modules\MailNotify\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;
use App\Models\Setting;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailNotifySettingController extends ModuleSettingController
{
    protected string $view = 'mail_notify::admin/settings/_mail_notify';

    protected string $route = 'mail-notify.settings';

    /**
     * Сохранение настроек
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = new Validator();
        $days = $request->integer('sets.sendprivatmailday');
        $packet = $request->integer('sets.sendmailpacket');

        $validator
            ->between($days, 1, 99, ['sets[sendprivatmailday]' => __('mail_notify::mail_notify.days_invalid')])
            ->between($packet, 1, 999, ['sets[sendmailpacket]' => __('mail_notify::mail_notify.packet_invalid')]);

        if ($validator->fails()) {
            return redirect()->route($this->route)
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        // Пишем только свои ключи: чужие имена из формы не должны попадать в настройки
        foreach (['sendprivatmailday' => $days, 'sendmailpacket' => $packet] as $name => $value) {
            Setting::query()->updateOrCreate(['name' => $name], ['value' => (string) $value]);
        }

        clearCache('settings');

        return redirect()->route($this->route)
            ->with('success', __('settings.settings_success_saved'));
    }
}
