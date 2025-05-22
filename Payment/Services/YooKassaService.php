<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Models\Order;
use RuntimeException;

class YooKassaService
{
    private string $shopId;
    private string $secretKey;
    private string $apiUrl;
    private string $currency;

    /** Ожидает оплаты покупателем */
    public const PENDING = 'pending';

    /** Ожидает подтверждения магазином */
    public const WAITING_FOR_CAPTURE = 'waiting_for_capture';

    /** Успешно оплачен и подтвержден магазином */
    public const SUCCEEDED = 'succeeded';

    /** Неуспех оплаты или отменен магазином */
    public const CANCELED = 'canceled';

    /** Ошибка */
    public const ERROR = 'error';

    /** Статусы платежа */
    public const STATUSES = [
        self::PENDING             => 'Платеж создан',
        self::WAITING_FOR_CAPTURE => 'Платеж оплачен и ожидает списание',
        self::SUCCEEDED           => 'Платеж успешно завершен',
        self::CANCELED            => 'Платеж отменен',
        self::ERROR               => 'Ошибка платежа',
    ];

    public function __construct()
    {
        $this->apiUrl = config('payment.yookassa_api_url');
        $this->shopId = config('payment.yookassa_shop_id');
        $this->secretKey = config('payment.yookassa_secret_key');
        $this->currency = config('payment.yookassa_currency');
    }

    /**
     * Создание платежа
     */
    public function createPayment(Order $order): array
    {
        $url = $this->apiUrl . '/payments';
        $idempotenceKey = uniqid('', true);

        try {
            $data = [
                'amount' => [
                    'value'    => $order->amount,
                    'currency' => $this->currency,
                ],
                'confirmation' => [
                    'type'       => 'redirect',
                    'return_url' => asset('payments/status?token=' . $order->token),
                ],
                'capture'     => true,
                'description' => $order->description . ' #' . $order->id,
                'receipt'     => [
                    'customer' => [
                        'email' => $order->email,
                    ],
                    'items' => [
                        [
                            'description' => $order->description,
                            'amount'      => [
                                'value'    => $order->amount,
                                'currency' => $this->currency,
                            ],
                            'quantity'        => 1,
                            'vat_code'        => 1, // НДС 0% (для самозанятых)
                            'payment_mode'    => 'full_payment', // Полная предоплата
                            'payment_subject' => 'service', // Услуга
                        ],
                    ],
                ],
            ];

            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->retry(3, 100)
                ->withHeaders([
                    'Idempotence-Key' => $idempotenceKey,
                    'Content-Type'    => 'application/json',
                ])
                ->post($url, $data)
                ->throw();

            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            Log::error('YooKassa connection failed', [
                'error' => $e->getMessage(),
                'url'   => $url,
            ]);
            throw new RuntimeException('Не удалось подключиться к YooKassa');
        } catch (RequestException $e) {
            $error = $e->response?->json() ?? $e->getMessage();
            Log::critical('YooKassa API error', [
                'error' => $error,
            ]);
            throw new RuntimeException('Ошибка API YooKassa');
        }
    }

    /**
     * Проверка статуса платежа
     */
    public function getPaymentInfo(string $paymentId): array
    {
        $url = $this->apiUrl . '/payments/' . $paymentId;

        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->retry(3, 100)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($url)
                ->throw();

            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            Log::error('YooKassa connection failed', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
                'url'        => $url,
            ]);
            throw new RuntimeException('Не удалось подключиться к API YooKassa');
        } catch (RequestException $e) {
            $error = $e->response?->json() ?? $e->getMessage();
            Log::critical('YooKassa API error', [
                'payment_id' => $paymentId,
                'error'      => $error,
            ]);
            throw new RuntimeException('Ошибка API YooKassa');
        }
    }

    /**
     * Обработка ответа
     */
    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        Log::error('YooKassa API Error', [
            'status' => $response->status(),
            'error'  => $response->json('description'),
        ]);

        throw new RuntimeException('Ошибка API YooKassa');
    }
}
