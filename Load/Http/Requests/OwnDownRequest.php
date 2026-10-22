<?php

declare(strict_types=1);

namespace Modules\Load\Http\Requests;

use Modules\Load\Models\Down;

/**
 * Правка своей загрузки на сайте и в API
 *
 * Владелец и статус проверяются до полей: чужая загрузка — 404, а не ошибки формы
 */
class OwnDownRequest extends DownRequest
{
    protected function prepareForValidation(): void
    {
        $down = Down::query()->where('user_id', getUser('id'))->find($this->route('id'));

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        // Проверенную загрузку правит только администрация
        if ($down->active) {
            abort($this->expectsJson() ? 422 : 200, __('load::loads.down_verified'));
        }

        parent::prepareForValidation();
    }
}
