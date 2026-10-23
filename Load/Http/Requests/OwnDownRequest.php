<?php

declare(strict_types=1);

namespace Modules\Load\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Load\Models\Down;

/**
 * Правка своей загрузки на сайте и в API
 *
 * Права проверяются до полей: чужая или проверенная загрузка — отказ, а не ошибки формы
 */
class OwnDownRequest extends DownRequest
{
    private ?Down $down = null;

    public function authorize(): Response
    {
        return Gate::inspect('update', $this->down());
    }

    /**
     * Правимая загрузка из маршрута
     */
    public function down(): Down
    {
        return $this->down ??= Down::query()->findOrFail(int($this->route('id')));
    }
}
