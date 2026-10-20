<?php

declare(strict_types=1);

namespace Modules\MailNotify\Http\Requests;

use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Настройки рассылок
 */
#[RedirectToRoute('mail-notify.settings')]
class MailNotifySettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sets.sendprivatmailday' => ['required', 'integer', 'between:1,99'],
            'sets.sendmailpacket'    => ['required', 'integer', 'between:1,999'],
        ];
    }

    public function messages(): array
    {
        return [
            'sets.sendprivatmailday' => __('mail_notify::mail_notify.days_invalid'),
            'sets.sendmailpacket'    => __('mail_notify::mail_notify.packet_invalid'),
        ];
    }
}
