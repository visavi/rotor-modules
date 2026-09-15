<?php

declare(strict_types=1);

namespace Modules\Notifier\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Notifier\Support\Notifier;

class NotifierController extends Controller
{
    /**
     * Количество непрочитанных сообщений
     *
     * Ответ максимально дешёвый: только число, без рендера диалогов.
     * Визит не сохраняется — маршрут исключает SaveStatistic и CheckUserState
     */
    public function check(): JsonResponse
    {
        $user = getUser();

        if (! $user || ! $user->isActive() || ! setting('notifier_active')) {
            return $this->json(['auth' => false, 'count' => 0]);
        }

        return $this->json(['auth' => true, 'count' => Notifier::unreadCount($user)]);
    }

    /**
     * Ответ без кеширования на стороне браузера и прокси
     *
     * @param array<string, mixed> $data
     */
    private function json(array $data): JsonResponse
    {
        return response()
            ->json($data)
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
