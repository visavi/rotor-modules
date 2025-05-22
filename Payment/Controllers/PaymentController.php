<?php

declare(strict_types=1);

namespace Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Models\Order;
use Modules\Payment\Services\OrderService;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Services\YooKassaService;

class PaymentController extends Controller
{
    public function __construct(
        private readonly YooKassaService $yooKassaService,
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {
        //
    }

    /**
     * Webhook
     */
    public function webhook(Request $request): Response
    {
        if (! $request->has('object.id') || ! $request->has('event')) {
            Log::warning('YooKassa webhook: Invalid webhook data');

            return response()->noContent(400);
        }

        $paymentId = $request->input('object.id');

        $order = $this->orderService->getOrderByField('payment_id', $paymentId);
        if (! $order) {
            Log::warning('YooKassa webhook: Order not found', ['payment_id' => $paymentId]);

            return response()->noContent(200);
        }

        try {
            $payment = $this->yooKassaService->getPaymentInfo($order->payment_id);
            $status = $payment['status'] ?? null;

            if ($status === YooKassaService::SUCCEEDED) {
                DB::transaction(function () use ($order, $status) {
                    if ($order->fresh()->status === YooKassaService::SUCCEEDED) {
                        return;
                    }

                    if ($order->type === Order::TYPE_ADVERT) {
                        $this->paymentService->createAdvert($order);
                    }

                    $order->update(['status' => $status]);
                });
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->noContent(400);
        }

        return response()->noContent(200);
    }
}
