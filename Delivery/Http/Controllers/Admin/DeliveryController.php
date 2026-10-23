<?php

declare(strict_types=1);

namespace Modules\Delivery\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Delivery\Http\Requests\DeliveryRequest;

class DeliveryController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        return view('delivery::admin/delivery/index');
    }

    /**
     * Рассылка
     */
    public function send(DeliveryRequest $request): RedirectResponse
    {
        $users = $this->recipients($request->integer('type'))
            ->where('id', '<>', auth()->id())
            ->get();

        if ($users->isEmpty()) {
            return redirect('admin/delivery')
                ->withInput()
                ->withErrors(['type' => __('delivery::delivery.not_recipients')]);
        }

        $text = $request->string('text')->value();

        foreach ($users as $user) {
            $user->sendMessage(null, $text);
        }

        return redirect('admin/delivery')
            ->with('success', __('delivery::delivery.success_sent'));
    }

    /**
     * Получатели рассылки по группе
     *
     * @return Builder<User>
     */
    private function recipients(int $type): Builder
    {
        return match ($type) {
            // Пользователи в онлайне
            1 => User::query()->whereHas('online'),
            // Активные: посещали сайт менее недели назад
            2 => User::query()->where('updated_at', '>', now()->subWeek()),
            // Администрация
            3 => User::query()->whereIn('level', User::ADMIN_GROUPS),
            // Все пользователи сайта
            default => User::query()->whereIn('level', User::USER_GROUPS),
        };
    }
}
