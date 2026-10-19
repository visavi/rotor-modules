<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Payment\Models\Order;
use Modules\Payment\Requests\BalanceRequest;
use Modules\Payment\Services\OrderService;
use Modules\Payment\Services\PaymentService;

class BalanceController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {
        // Выключенное пополнение отвечает 404 раньше валидации формы
        $this->middleware(function ($request, $next) {
            if (! $this->paymentService->isBalanceEnabled()) {
                abort(404);
            }

            return $next($request);
        });
    }

    /**
     * Пополнение баланса
     */
    public function index(): View
    {
        $rate = $this->paymentService->getBalanceRate();
        $min = $this->paymentService->getBalanceMin();
        $max = $this->paymentService->getBalanceMax();
        $amount = $this->paymentService->getBalanceDefault();

        return view('payment::balance/index', compact('rate', 'min', 'max', 'amount'));
    }

    /**
     * Оплата пополнения
     */
    public function pay(BalanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $amount = (int) $validated['amount'];

        // Монеты фиксируются в заказе: смена курса, пока покупатель платит, на начисление не влияет
        $coins = $amount * $this->paymentService->getBalanceRate();

        try {
            $order = $this->orderService->createOrder([
                'type'        => Order::TYPE_BALANCE,
                'coins'       => $coins,
                'email'       => $validated['email'],
                'description' => __('payment::payments.balance.order_description', [
                    'money' => plural($coins, setting('moneyname')),
                ]),
                'prices' => [
                    'total' => $amount,
                ],
            ]);

            return redirect()->away($this->paymentService->startPayment($order));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect('/payments/balance')
                ->withErrors($e->getMessage());
        }
    }
}
