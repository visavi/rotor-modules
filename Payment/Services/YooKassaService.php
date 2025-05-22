<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class YooKassaService
{
    private string $shopId;
    private string $secretKey;
    private string $apiUrl;

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
        $this->apiUrl = config('Payment.yookassa_api_url');
        $this->shopId = config('Payment.yookassa_shop_id');
        $this->secretKey = config('Payment.yookassa_secret_key');
    }

    /**
     * Создание платежа
     */
    public function createPayment(
        int|float $amount,
        string $returnUrl,
        string $description
    ): array {
        $url = $this->apiUrl . '/payments';
        $idempotenceKey = uniqid('', true);

        try {
            $data = [
                'amount' => [
                    'value'    => $amount,
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type'       => 'redirect',
                    'return_url' => $returnUrl,
                ],
                'capture'     => true,
                'description' => $description,
            ];

            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->retry(3, 100)
                ->withHeaders([
                    'Idempotence-Key' => $idempotenceKey,
                    'Content-Type'    => 'application/json',
                ])
                ->post($url, $data);

            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            Log::error('YooKassa connection failed', [
                'error' => $e->getMessage(),
                'url'   => $url,
            ]);
            throw new RuntimeException('Не удалось подключиться к YooKassa');
        } catch (Exception $e) {
            Log::critical('YooKassa API error', [
                'error' => $e->getMessage(),
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
                ->get($url);

            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            Log::error('YooKassa connection failed', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
                'url'        => $url,
            ]);
            throw new RuntimeException('Не удалось подключиться к API YooKassa');
        } catch (Exception $e) {
            Log::critical('YooKassa API error', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
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
