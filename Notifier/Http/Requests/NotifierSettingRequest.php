<?php

declare(strict_types=1);

namespace Modules\Notifier\Http\Requests;

use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notifier\Support\Notifier;

/**
 * Настройки уведомлений о новых письмах
 *
 * При выключенной проверке остальные поля заблокированы в форме
 * и браузер их не отправляет — проверяется только пришедшее (sometimes),
 * непришедшее сохраняет прежнее значение. Пустой звук — «без звука»
 */
#[RedirectToRoute('notifier.settings')]
class NotifierSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sets.notifier_interval' => ['sometimes', 'integer', 'between:' . Notifier::MIN_INTERVAL . ',' . Notifier::MAX_INTERVAL],
            'sets.notifier_volume'   => ['sometimes', 'integer', 'between:0,100'],
            'sets.notifier_sound'    => ['nullable', Rule::in(array_keys(Notifier::sounds()))],
        ];
    }

    public function messages(): array
    {
        return [
            'sets.notifier_interval' => __('notifier::notifier.interval_invalid', [
                'min' => Notifier::MIN_INTERVAL,
                'max' => Notifier::MAX_INTERVAL,
            ]),
            'sets.notifier_volume' => __('notifier::notifier.volume_invalid'),
            'sets.notifier_sound'  => __('notifier::notifier.sound_invalid'),
        ];
    }
}
