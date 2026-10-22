<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Advert\Http\Requests\AdvertPurchaseRequest;
use Modules\Advert\Models\Advert;

class AdvertController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $this->checkOpen();

        $adverts = Advert::query()
            ->where('type', Advert::TYPE_USER)
            ->where('deleted_at', '>', now())
            ->orderByDesc('deleted_at')
            ->with('user')
            ->paginate(setting('rekuserpost'));

        return view('advert::adverts/index', compact('adverts'));
    }

    /**
     * Покупка рекламы
     */
    public function create(): View
    {
        $this->checkCanBuy();

        return view('advert::adverts/create');
    }

    /**
     * Оплата и размещение ссылки
     *
     * Баланс проверяется ещё раз при списании: два одновременных запроса
     * проходят проверку оба, и без условия баланс ушёл бы в минус
     */
    public function store(AdvertPurchaseRequest $request): RedirectResponse
    {
        $this->checkCanBuy();

        $user = getUser();
        $price = $request->price();

        DB::transaction(static function () use ($request, $user, $price) {
            if (! $user->chargeMoney($price)) {
                throw ValidationException::withMessages(['money' => __('advert::adverts.advert_not_money')]);
            }

            Advert::query()->create($request->advertFields() + [
                'type'       => Advert::TYPE_USER,
                'user_id'    => $user->id,
                'deleted_at' => now()->addHours((int) setting('rekusertime')),
            ]);
        });

        clearCache('adverts');

        return redirect('adverts')
            ->with('success', __('advert::adverts.advert_success_posted'));
    }

    /**
     * Раздел включается настройкой
     */
    private function checkOpen(): void
    {
        if (! setting('rekuseractive')) {
            abort(200, __('advert::adverts.advert_closed'));
        }
    }

    /**
     * Купить ссылку можно, набрав порог баллов, и только одну за раз
     */
    private function checkCanBuy(): void
    {
        $this->checkOpen();

        if (getUser('point') < setting('rekuserpoint')) {
            abort(200, __('advert::adverts.advert_point', ['point' => plural((int) setting('rekuserpoint'), setting('scorename'))]));
        }

        Advert::query()->where('type', Advert::TYPE_USER)->where('deleted_at', '<', now())->delete();

        $posted = Advert::query()
            ->where('type', Advert::TYPE_USER)
            ->where('user_id', getUser('id'))
            ->exists();

        if ($posted) {
            abort(200, __('advert::adverts.advert_already_posted'));
        }
    }
}
