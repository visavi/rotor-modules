<?php

declare(strict_types=1);

namespace Modules\Game\Http\Concerns;

use App\Support\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RejectsInvalidInput
{
    /**
     * Ответ на непройденную проверку для ajax-запроса
     *
     * Ставки уходят ajax-ом, а редирект с withErrors до игрока не доходит:
     * страница не перезагружается, и ошибка остаётся невидимой. Здесь она
     * возвращается тем же json, что понимает ajax ядра, и всплывает
     * уведомлением. Для обычной отправки формы метод возвращает null,
     * и контроллер отвечает прежним редиректом
     */
    private function ajaxError(Request $request, Validator $validator): ?JsonResponse
    {
        if (! $request->ajax()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => implode(' ', $validator->getErrors()),
        ]);
    }
}
