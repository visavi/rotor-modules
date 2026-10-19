<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Requests\CardRequest;
use Modules\Payment\Requests\MyCardRequest;
use Modules\Payment\Services\CardImageService;
use Modules\Payment\Services\PaymentService;

class CardController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CardImageService $cardImageService,
    ) {
        //
    }

    /**
     * Покупка карточки
     */
    public function index(): View
    {
        $places = $this->paymentService->getCardPlaces();

        return view('payment::card/create', compact('places'));
    }

    /**
     * Расчёт стоимости
     *
     * Картинка сохраняется здесь: дальше данные уходят в оплату зашифрованной
     * строкой, файл через неё не передать. Картинки неоплаченных заказов
     * убирает команда payment:clean-card-images
     */
    public function calculate(CardRequest $request): View
    {
        $validated = $request->validated();

        $card = [
            'type'        => Order::TYPE_CARD,
            'place'       => $validated['place'],
            'url'         => $validated['url'],
            'title'       => $validated['title'],
            'text'        => $validated['text'] ?? null,
            'image'       => $request->hasFile('image') ? $this->cardImageService->store($request->file('image')) : null,
            'term'        => (int) $validated['term'],
            'email'       => $validated['email'],
            'description' => __('payment::payments.cards.order_description', ['title' => $validated['title']]),
            // Место и цена проверены сейчас: оплата старой страницы расчёта проверит их заново
            'expires_at' => now()->addHour()->getTimestamp(),
        ];

        $card['prices'] = $this->paymentService->calculateCard($card);

        $data = Crypt::encrypt($card);

        return view('payment::card/calculate', compact('card', 'data'));
    }

    /**
     * Редактирование своей карточки
     */
    public function edit(int $id): View
    {
        $card = PaidAdvert::query()
            ->ownActive()
            ->whereIn('place', PaidAdvert::CARD_PLACES)
            ->find($id);

        if (! $card) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        return view('payment::card/edit', compact('card'));
    }

    /**
     * Сохранение своей карточки
     *
     * Новая картинка не проверяется заранее, поэтому о ней узнают админы
     */
    public function update(MyCardRequest $request): RedirectResponse
    {
        $card = $request->card();
        $validated = $request->validated();

        $oldImage = $image = $card->image;
        $imageChanged = false;

        if ($request->hasFile('image')) {
            $image = $this->cardImageService->store($request->file('image'));
            $imageChanged = true;
        } elseif ($request->boolean('remove_image')) {
            $image = null;
        }

        $card->update([
            'names' => [$validated['title']],
            'text'  => $validated['text'] ?? null,
            'url'   => $validated['url'],
            'image' => $image,
        ]);

        $this->cardImageService->delete($oldImage);

        clearCache('paidCards');

        if ($imageChanged) {
            $this->paymentService->notifyBosses($card, 'cards.notify_image');
        }

        return redirect('/payments/my')
            ->with('success', __('payment::payments.cards.card_saved'));
    }
}
