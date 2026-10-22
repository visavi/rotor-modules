<?php

declare(strict_types=1);

namespace Modules\MailNotify\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class MailNotifySettingController extends ModuleSettingController
{
    protected string $view = 'mail_notify::admin/settings/_mail_notify';

    protected string $route = 'mail-notify.settings';

    protected function rules(): array
    {
        return [
            'sets.sendprivatmailday' => ['required', 'integer', 'between:1,99'],
            'sets.sendmailpacket'    => ['required', 'integer', 'between:1,999'],
        ];
    }

    protected function messages(): array
    {
        return [
            'sets.sendprivatmailday' => __('mail_notify::mail_notify.days_invalid'),
            'sets.sendmailpacket'    => __('mail_notify::mail_notify.packet_invalid'),
        ];
    }
}
