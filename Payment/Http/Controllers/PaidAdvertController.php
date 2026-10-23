<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Requests\AdminAdvertRequest;
use Modules\Payment\Requests\AdminCardRequest;
use Modules\Payment\Services\CardImageService;

class PaidAdvertController extends AdminController
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly CardImageService $cardImageService,
    ) {
        //
    }

    /**
     * List adverts
     */
    public function index(Request $request): View
    {
        $place = $request->input('place', 'top_all');

        $places = [...PaidAdvert::PLACES, ...PaidAdvert::CARD_PLACES];

        if (! in_array($place, $places, true)) {
            $place = 'top_all';
        }

        $advertTotal = PaidAdvert::query()
            ->selectRaw('place, count(*) as total')
            ->groupBy('place')
            ->pluck('total', 'place')
            ->all();

        $totals = [];
        foreach ($places as $placeName) {
            $totals[$placeName] = $advertTotal[$placeName] ?? 0;
        }

        $adverts = PaidAdvert::query()
            ->where('place', $place)
            ->orderBy('created_at')
            ->with('user')
            ->get();

        return view('payment::admin/paid-adverts/index', compact('adverts', 'place', 'places', 'totals'));
    }

    /**
     * Форма добавления рекламной ссылки
     */
    public function create(Request $request): View
    {
        $places = PaidAdvert::PLACES;
        $advert = new PaidAdvert();
        $place = $request->input('place');

        return view('payment::admin/paid-adverts/create', compact('advert', 'places', 'place'));
    }

    /**
     * Добавление рекламной ссылки
     */
    public function store(AdminAdvertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        PaidAdvert::query()->create([
            'user_id' => auth()->id(),
            ...$this->advertData($request),
        ]);

        clearCache('paidAdverts');

        return redirect('admin/paid-adverts?place=' . $validated['place'])
            ->with('success', __('main.record_added_success'));
    }

    /**
     * Форма редактирования рекламной ссылки
     */
    public function edit(int $id, Request $request): View
    {
        $places = PaidAdvert::PLACES;
        $advert = PaidAdvert::query()->whereIn('place', $places)->find($id);

        if (! $advert) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        $place = $request->input('place');

        return view('payment::admin/paid-adverts/edit', compact('advert', 'places', 'place'));
    }

    /**
     * Сохранение рекламной ссылки
     */
    public function update(AdminAdvertRequest $request): RedirectResponse
    {
        $advert = $request->advert();
        $advert->update($this->advertData($request));

        clearCache('paidAdverts');

        return redirect('admin/paid-adverts?place=' . $advert->place)
            ->with('success', __('main.record_saved_success'));
    }

    /**
     * Снятие рекламы с показа: срок истекает сейчас, запись остаётся в списке.
     * Вернуть — новой датой в правке
     */
    public function stop(int $id): RedirectResponse
    {
        $advert = PaidAdvert::query()->active()->find($id);

        if (! $advert) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        $advert->update(['deleted_at' => now()]);

        clearCache(['paidAdverts', 'paidCards']);

        return redirect('admin/paid-adverts?place=' . $advert->place)
            ->with('success', __('payment::payments.paid_adverts.stopped'));
    }

    /**
     * Delete adverts
     */
    public function delete(int $id): RedirectResponse
    {
        $advert = PaidAdvert::query()->find($id);

        if (! $advert) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        $advert->delete();
        $this->cardImageService->delete($advert->image);

        clearCache(['paidAdverts', 'paidCards']);

        return redirect('admin/paid-adverts?place=' . $advert->place)
            ->with('success', __('main.record_deleted_success'));
    }

    /**
     * Форма добавления рекламной карточки без оплаты
     */
    public function createCard(Request $request): View
    {
        $card = new PaidAdvert(['place' => $request->input('place')]);

        return view('payment::admin/paid-adverts/edit-card', compact('card'));
    }

    /**
     * Добавление рекламной карточки без оплаты
     *
     * Лимит места не проверяется: админ ставит карточку сверх очереди сознательно
     */
    public function storeCard(AdminCardRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        PaidAdvert::query()->create([
            'user_id'    => auth()->id(),
            'place'      => $validated['place'],
            'url'        => $validated['url'],
            'names'      => [$validated['title']],
            'text'       => $validated['text'] ?? null,
            'image'      => $request->hasFile('image') ? $this->cardImageService->store($request->file('image')) : null,
            'deleted_at' => $validated['term'],
        ]);

        clearCache('paidCards');

        return redirect('admin/paid-adverts?place=' . $validated['place'])
            ->with('success', __('main.record_added_success'));
    }

    /**
     * Форма редактирования рекламной карточки
     */
    public function editCard(int $id): View
    {
        $card = PaidAdvert::query()->whereIn('place', PaidAdvert::CARD_PLACES)->find($id);

        if (! $card) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        return view('payment::admin/paid-adverts/edit-card', compact('card'));
    }

    /**
     * Сохранение рекламной карточки
     *
     * Плохую картинку снимают, не трогая карточку: покупатель получил
     * оплаченное место, возвращать деньги не за что
     */
    public function updateCard(AdminCardRequest $request): RedirectResponse
    {
        $card = $request->card();
        $validated = $request->validated();

        $oldImage = $image = $card->image;

        if ($request->hasFile('image')) {
            $image = $this->cardImageService->store($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $image = null;
        }

        $card->update([
            'names'      => [$validated['title']],
            'text'       => $validated['text'] ?? null,
            'url'        => $validated['url'],
            'image'      => $image,
            'deleted_at' => $validated['term'],
        ]);

        $this->cardImageService->delete($oldImage);

        clearCache('paidCards');

        return redirect('admin/paid-adverts?place=' . $card->place)
            ->with('success', __('main.record_saved_success'));
    }

    /**
     * Поля рекламной ссылки из проверенного запроса
     */
    private function advertData(AdminAdvertRequest $request): array
    {
        $validated = $request->validated();

        return [
            'place'      => $validated['place'],
            'url'        => $validated['url'],
            'names'      => $validated['names'],
            'color'      => $validated['color'] ?? null,
            'bold'       => $request->boolean('bold'),
            'comment'    => $validated['comment'] ?? null,
            'deleted_at' => $validated['term'],
        ];
    }
}
