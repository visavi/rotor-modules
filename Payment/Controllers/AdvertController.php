<?php

declare(strict_types=1);

namespace Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaidAdvert;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Payment\Models\Order;
use Modules\Payment\Requests\CalculateRequest;
use Modules\Payment\Requests\PayRequest;
use Modules\Payment\Services\OrderService;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Services\YooKassaService;
use RuntimeException;

class AdvertController extends Controller
{
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

        return view('Payment::advert/create', compact('advert', 'places'));
    }

    /**
     * Calculate
     */
    public function calculate(CalculateRequest $request): View
    {
        $validated = $request->validated();

        $advert = [
            'type'    => Order::TYPE_ADVERT,
            'place'   => $validated['place'],
            'site'    => $validated['site'],
            'names'   => $validated['names'],
            'color'   => $validated['color'],
            'bold'    => $validated['bold'],
            'term'    => $validated['term'],
            'comment' => $validated['comment'],
        ];

        $prices = $this->paymentService->calculateAdvert($advert);
        $advert = array_merge($advert, ['prices' => $prices]);

        $data = Crypt::encrypt($advert);

        return view('Payment::advert/calculate', compact('advert', 'data'));
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

            $payment = $this->yooKassaService->createPayment(
                $order->amount,
                asset('payments/status?token=' . $order->token),
                __('Payment::payments.payment_order', ['id' => $order->id])
            );

            if (! $payment || ! $payment['id']) {
                throw new RuntimeException(__('Payment::payments.payment_create_failed'));
            }

            if ($payment['status'] === YooKassaService::CANCELED) {
                throw new RuntimeException(__('Payment::payments.payment_creation_cancelled'));
            }

            // Проверяем ссылку для редиректа
            $confirmationUrl = $payment['confirmation']['confirmation_url'] ?? null;
            if (! $confirmationUrl) {
                throw new RuntimeException(__('Payment::payments.payment_link_failed'));
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
            abort(404, __('Payment::payments.payment_not_found'));
        }

        return view('Payment::advert.status', [
            'order'    => $order,
            'retryUrl' => '/payments/result?token=' . $order->token,
        ]);
    }
}
