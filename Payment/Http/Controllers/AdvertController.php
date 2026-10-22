<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Requests\CalculateRequest;
use Modules\Payment\Requests\MyAdvertRequest;
use Modules\Payment\Requests\PayRequest;
use Modules\Payment\Services\OrderService;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Services\YooKassaService;

class AdvertController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {
        //
    }

    /**
     * Main page
     */
    public function index(): View
    {
        $advert = new PaidAdvert();
        $places = $advert->getPlaces();
        $prices = $this->paymentService->getPrices();

        return view('payment::advert/create', compact('advert', 'places', 'prices'));
    }

    /**
     * Calculate
     */
    public function calculate(CalculateRequest $request): View
    {
        $validated = $request->validated();
        $renewed = $request->renewed();

        $advert = [
            'type'        => Order::TYPE_ADVERT,
            'place'       => $validated['place'],
            'url'         => $validated['url'],
            'names'       => $validated['names'],
            'color'       => $validated['color'],
            'bold'        => $validated['bold'],
            'term'        => $validated['term'],
            'comment'     => $validated['comment'],
            'email'       => $validated['email'],
            'description' => __('payment::payments.payment_order'),
            'renew_id'    => $renewed?->id,
        ];

        $prices = $this->paymentService->calculateAdvert($advert);
        $advert = array_merge($advert, ['prices' => $prices]);

        $data = Crypt::encrypt($advert);

        $renewUntil = $renewed ? $this->paymentService->renewEnd($renewed, $advert) : null;

        return view('payment::advert/calculate', compact('advert', 'data', 'renewUntil'));
    }

    /**
     * Pay
     */
    public function pay(PayRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $data = Crypt::decrypt($validated['data']);

            if (($data['type'] ?? null) === Order::TYPE_CARD
                && $error = $this->paymentService->validateCardOrder($data)
            ) {
                $url = empty($data['renew_id']) ? '/payments/card' : '/payments/my/card/renew/' . $data['renew_id'];

                return redirect($url)->withErrors($error);
            }

            $order = $this->orderService->createOrder($data);

            return redirect()->away($this->paymentService->startPayment($order));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect('/payments/advert')
                ->withErrors($e->getMessage());
        }
    }

    /**
     * Status
     */
    public function status(Request $request): View
    {
        $order = $this->orderService->getOrderByField('token', $request->input('token'));

        if (! $order) {
            abort(404, __('payment::payments.payment_not_found'));
        }

        return view('payment::advert.status', compact('order'));
    }

    /**
     * Моя реклама
     *
     * Истёкшая — за последние дни из config.php модуля, её можно продлить.
     * Конфиг кэшируется вместе со списком модулей: пока кэш не сброшен,
     * нового ключа в нём нет, и без значения по умолчанию список был бы пуст
     */
    public function my(): View
    {
        $adverts = PaidAdvert::query()
            ->ownActive()
            ->orderByDesc('created_at')
            ->get();

        $expired = PaidAdvert::query()
            ->own()
            ->where('deleted_at', '<=', now())
            ->where('deleted_at', '>', now()->subDays((int) config('payment.renew_expired_days', 90)))
            ->orderByDesc('deleted_at')
            ->get();

        // Неоплаченные заказы, у которых страница оплаты ещё жива
        $pendingOrders = Order::query()
            ->where('user_id', auth()->id())
            ->whereIn('type', [Order::TYPE_ADVERT, Order::TYPE_CARD])
            ->where('status', YooKassaService::PENDING)
            ->whereNotNull('payment_url')
            ->where('created_at', '>', now()->subSeconds((int) config('payment.confirmation_ttl')))
            ->orderByDesc('id')
            ->get();

        return view('payment::advert/my', compact('adverts', 'expired', 'pendingOrders'));
    }

    /**
     * Продление своей рекламы: форма покупки с данными рекламы, всё можно поменять
     */
    public function renew(int $id): View
    {
        $advert = PaidAdvert::query()->own()->whereNotIn('place', PaidAdvert::CARD_PLACES)->find($id);

        if (! $advert) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        // В комментарии записи — служебное описание заказа, покупателю его не подставляем
        $advert->comment = null;

        $places = $advert->getPlaces();
        $prices = $this->paymentService->getPrices();

        return view('payment::advert/renew', compact('advert', 'places', 'prices'));
    }

    /**
     * Редактирование своей рекламы
     */
    public function edit(int $id): View
    {
        $advert = PaidAdvert::query()->ownActive()->whereNotIn('place', PaidAdvert::CARD_PLACES)->find($id);

        if (! $advert) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        return view('payment::advert/edit', compact('advert'));
    }

    /**
     * Сохранение своей рекламы
     *
     * Оплаченные опции неизменяемы: жирность остаётся как при оплате,
     * оплаченный цвет можно менять, но не отключать,
     * количество названий фиксировано
     */
    public function update(MyAdvertRequest $request): RedirectResponse
    {
        $advert = $request->advert();
        $validated = $request->validated();

        $advert->update([
            'url'   => $validated['url'],
            'names' => array_values($validated['names']),
            'color' => $advert->color ? $validated['color'] : null,
        ]);

        clearCache('paidAdverts');

        return redirect('/payments/my')
            ->with('success', __('payment::payments.paid_adverts.advert_saved'));
    }
}
