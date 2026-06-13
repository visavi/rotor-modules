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
use RuntimeException;

class AdvertController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly YooKassaService $yooKassaService,
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

        $advert = [
            'type'        => Order::TYPE_ADVERT,
            'place'       => $validated['place'],
            'site'        => $validated['site'],
            'names'       => $validated['names'],
            'color'       => $validated['color'],
            'bold'        => $validated['bold'],
            'term'        => $validated['term'],
            'comment'     => $validated['comment'],
            'email'       => $validated['email'],
            'description' => __('payment::payments.payment_order'),
        ];

        $prices = $this->paymentService->calculateAdvert($advert);
        $advert = array_merge($advert, ['prices' => $prices]);

        $data = Crypt::encrypt($advert);

        return view('payment::advert/calculate', compact('advert', 'data'));
    }

    /**
     * Pay
     */
    public function pay(PayRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $data = Crypt::decrypt($validated['data']);
            $order = $this->orderService->createOrder($data);
            $payment = $this->yooKassaService->createPayment($order);

            if (! $payment || ! $payment['id']) {
                throw new RuntimeException(__('payment::payments.payment_create_failed'));
            }

            if ($payment['status'] === YooKassaService::CANCELED) {
                throw new RuntimeException(__('payment::payments.payment_creation_cancelled'));
            }

            // Проверяем ссылку для редиректа
            $confirmationUrl = $payment['confirmation']['confirmation_url'] ?? null;
            if (! $confirmationUrl) {
                throw new RuntimeException(__('payment::payments.payment_link_failed'));
            }

            $order->update([
                'status'      => $payment['status'],
                'payment_id'  => $payment['id'],
                'payment_url' => $confirmationUrl,
            ]);

            return redirect()->away($confirmationUrl);
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
     */
    public function my(): View
    {
        $adverts = PaidAdvert::query()
            ->ownActive()
            ->orderByDesc('created_at')
            ->get();

        // Неоплаченные заказы, у которых страница оплаты ещё жива
        $pendingOrders = Order::query()
            ->where('user_id', auth()->id())
            ->where('status', YooKassaService::PENDING)
            ->whereNotNull('payment_url')
            ->where('created_at', '>', now()->subSeconds((int) config('payment.confirmation_ttl')))
            ->orderByDesc('id')
            ->get();

        return view('payment::advert/my', compact('adverts', 'pendingOrders'));
    }

    /**
     * Редактирование своей рекламы
     */
    public function edit(int $id): View
    {
        $advert = PaidAdvert::query()->ownActive()->find($id);

        if (! $advert) {
            abort(404, __('payment::payments.paid_adverts.not_found'));
        }

        return view('payment::advert/edit', compact('advert'));
    }

    /**
     * Сохранение своей рекламы
     *
     * Оплаченные опции заблокированы: нельзя включить цвет или жирность,
     * если их не было при оплате, и добавить названия сверх оплаченных
     */
    public function update(MyAdvertRequest $request): RedirectResponse
    {
        $advert = $request->advert();
        $validated = $request->validated();

        $advert->update([
            'site'  => $validated['site'],
            'names' => array_values($validated['names']),
            'color' => $advert->color ? ($validated['color'] ?? null) : $advert->color,
            'bold'  => $advert->bold && $request->boolean('bold'),
        ]);

        clearCache('paidAdverts');

        setFlash('success', __('payment::payments.paid_adverts.advert_saved'));

        return redirect('/payments/my');
    }
}
