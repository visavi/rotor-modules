<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
use RuntimeException;
use Throwable;

class PaymentService
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly YooKassaService $yooKassaService,
    ) {
        //
    }

    /**
     * Создаёт платёж в ЮКассе и возвращает ссылку на страницу оплаты
     *
     * Если платёж создать не удалось, заказ получает статус ошибки:
     * иначе он остаётся в админке без статуса и непонятно, что с ним
     */
    public function startPayment(Order $order): string
    {
        try {
            $payment = $this->yooKassaService->createPayment($order);

            if (! $payment || ! $payment['id']) {
                throw new RuntimeException(__('payment::payments.payment_create_failed'));
            }

            if ($payment['status'] === YooKassaService::CANCELED) {
                throw new RuntimeException(__('payment::payments.payment_creation_cancelled'));
            }

            $confirmationUrl = $payment['confirmation']['confirmation_url'] ?? null;
            if (! $confirmationUrl) {
                throw new RuntimeException(__('payment::payments.payment_link_failed'));
            }
        } catch (Throwable $e) {
            $order->update(['status' => YooKassaService::ERROR]);

            throw $e;
        }

        $order->update([
            'status'      => $payment['status'],
            'payment_id'  => $payment['id'],
            'payment_url' => $confirmationUrl,
        ]);

        return $confirmationUrl;
    }

    /**
     * Выполняет оплаченный заказ
     */
    public function fulfill(Order $order): void
    {
        match ($order->type) {
            Order::TYPE_ADVERT  => $this->createAdvert($order),
            Order::TYPE_BALANCE => $this->creditBalance($order),
            Order::TYPE_CARD    => $this->createCard($order),
            default             => null,
        };
    }

    /**
     * Создаёт оплаченную рекламную карточку
     *
     * Карточка встаёт на сайт сразу, без проверки: админы узнают о ней
     * из уведомления и снимают нарушителя сами
     */
    public function createCard(Order $order): PaidAdvert
    {
        $card = PaidAdvert::query()->create([
            'user_id'    => $order->user_id,
            'place'      => $order->data['place'],
            'url'        => $order->data['url'],
            'names'      => [$order->data['title']],
            'text'       => $order->data['text'],
            'image'      => $order->data['image'],
            'comment'    => $order->data['description'] . ' #' . $order->id,
            'deleted_at' => now()->addDays((int) $order->data['term']),
        ]);

        clearCache('paidCards');

        $this->notifyBosses($card, 'cards.notify_new');

        return $card;
    }

    /**
     * Сообщает владельцам сайта о новой карточке или смене её картинки
     *
     * Текст сообщения — HTML: данные покупателя экранируются,
     * язык — из профиля получателя (вебхук шлёт сервер ЮКассы)
     */
    public function notifyBosses(PaidAdvert $card, string $key): void
    {
        $link = url('/admin/paid-adverts/edit-card/' . $card->id);

        $bosses = User::query()->where('level', User::BOSS)->get();

        foreach ($bosses as $boss) {
            $locale = $boss->language ?: null;

            $boss->sendMessage(null, __('payment::payments.' . $key, [
                'place' => e(__('payment::payments.cards.' . $card->place, [], $locale)),
                'title' => e($card->names[0]),
                'text'  => e((string) $card->text),
                'url'   => '<a href="' . e($card->url) . '">' . e($card->url) . '</a>',
                'link'  => '<a href="' . e($link) . '">' . e($link) . '</a>',
            ], $locale));
        }
    }

    /**
     * Начисляет монеты за пополнение баланса
     *
     * Число монет записано в заказ при создании: смена курса,
     * пока покупатель платит, на начисление не влияет
     */
    public function creditBalance(Order $order): void
    {
        $user = User::query()->find($order->user_id);

        if (! $user) {
            Log::warning('Payment: balance order without user', ['order_id' => $order->id]);

            return;
        }

        $coins = (int) $order->data['coins'];

        // Запрос шлёт сервер ЮКассы: язык уведомления берётся из профиля, а не из запроса
        $user->increment('money', $coins);
        $user->sendMessage(null, __('payment::payments.balance.credited', [
            'money' => plural($coins, setting('moneyname')),
        ], $user->language ?: null));
    }

    /**
     * Включено ли пополнение баланса
     */
    public function isBalanceEnabled(): bool
    {
        return setting('payment_balance_enabled') && $this->getBalanceRate() > 0;
    }

    /**
     * Сколько монет даётся за 1 рубль
     */
    public function getBalanceRate(): int
    {
        return (int) setting('payment_balance_rate', 0);
    }

    /**
     * Минимальная сумма пополнения в рублях
     */
    public function getBalanceMin(): int
    {
        return max((int) setting('payment_balance_min', 1), 1);
    }

    /**
     * Предел суммы одного пополнения в рублях
     */
    public function getBalanceMax(): int
    {
        return (int) config('payment.balance_max_amount');
    }

    /**
     * Сумма, которую подставить в форму
     */
    public function getBalanceDefault(): int
    {
        return min(max((int) config('payment.balance_default_amount'), $this->getBalanceMin()), $this->getBalanceMax());
    }

    /**
     * Create paid advert
     */
    public function createAdvert(Order $order): PaidAdvert
    {
        $advert = PaidAdvert::query()->create([
            'user_id'    => $order->user_id,
            'place'      => $order->data['place'],
            'url'        => $order->data['url'],
            'names'      => $order->data['names'],
            'color'      => $order->data['color'],
            'bold'       => $order->data['bold'],
            'comment'    => $order->data['description'] . ' #' . $order->id . ' ' . $order->data['comment'],
            'deleted_at' => now()->addDays((int) $order->data['term']),
        ]);

        clearCache('paidAdverts');

        return $advert;
    }

    /**
     * Возвращает цены за сутки размещения
     */
    public function getPrices(): array
    {
        $places = [];
        foreach (PaidAdvert::PLACES as $place) {
            $places[$place] = setting('payment_price_' . $place, 0);
        }

        return [
            'places' => $places,
            'color'  => setting('payment_price_color', 0),
            'bold'   => setting('payment_price_bold', 0),
            'name'   => setting('payment_price_name', 0),
        ];
    }

    /**
     * Calculate
     */
    public function calculateAdvert(array $data): array
    {
        $countNames = count($data['names']);
        $prices = $this->getPrices();

        $placePrice = $prices['places'][$data['place']] ?? 0;
        $colorPrice = $prices['color'];
        $boldPrice = $prices['bold'];
        $namePrice = $prices['name'];

        $placePrice = $data['term'] * $placePrice;
        $colorPrice = $data['color'] ? $data['term'] * $colorPrice : 0;
        $boldPrice = $data['bold'] ? $data['term'] * $boldPrice : 0;
        $namesPrice = $countNames > 1 ? $data['term'] * $namePrice * ($countNames - 1) : 0;

        $total = $placePrice + $colorPrice + $boldPrice + $namesPrice;

        return [
            'total' => $total,
            'place' => $placePrice,
            'color' => $colorPrice,
            'bold'  => $boldPrice,
            'names' => $namesPrice,
        ];
    }

    /**
     * Цена карточки за сутки, 0 — место выключено
     */
    public function getCardPrice(string $place): int
    {
        return (int) setting('payment_price_' . $place, 0);
    }

    /**
     * Сколько карточек одновременно показывается на одном месте
     */
    public function getCardLimit(): int
    {
        return max((int) setting('payment_card_limit', 3), 1);
    }

    /**
     * Места карточек, которые продаются, с ценой и занятостью
     *
     * freeAt — когда освободится ближайшее, если место занято целиком
     *
     * @return array<string, array{name: string, price: int, taken: int, limit: int, freeAt: ?CarbonImmutable}>
     */
    public function getCardPlaces(): array
    {
        $limit = $this->getCardLimit();
        $places = [];

        foreach (PaidAdvert::CARD_PLACES as $place) {
            $price = $this->getCardPrice($place);

            if ($price <= 0) {
                continue;
            }

            $active = PaidAdvert::query()
                ->active()
                ->where('place', $place)
                ->orderBy('deleted_at')
                ->pluck('deleted_at');

            $places[$place] = [
                'name'   => __('payment::payments.cards.' . $place),
                'price'  => $price,
                'taken'  => $active->count(),
                'limit'  => $limit,
                'freeAt' => $active->count() >= $limit ? $active->first() : null,
            ];
        }

        return $places;
    }

    /**
     * Стоимость карточки за весь срок
     *
     * @return array{total: int, place: int}
     */
    public function calculateCard(array $data): array
    {
        $total = (int) $data['term'] * $this->getCardPrice($data['place']);

        return [
            'total' => $total,
            'place' => $total,
        ];
    }

    /**
     * Проверяет заказ карточки перед оплатой, возвращает ошибку или null
     *
     * Данные расчёта зашифрованы, но могут устареть: место заняли, цену
     * поменяли, страницу оплатили дважды. После оплаты проверять поздно —
     * деньги уже списаны
     */
    public function validateCardOrder(array $data): ?string
    {
        if (! getUser()) {
            return __('payment::payments.cards.login_required');
        }

        if (($data['expires_at'] ?? 0) < now()->getTimestamp()) {
            return __('payment::payments.cards.payload_expired');
        }

        if (! $this->isCardPlaceAvailable((string) $data['place'])) {
            return __('payment::payments.cards.place_unavailable');
        }

        if ($this->calculateCard($data)['total'] !== (int) $data['prices']['total']) {
            return __('payment::payments.cards.price_changed');
        }

        return null;
    }

    /**
     * Можно ли купить карточку на месте: продаётся и есть свободное
     */
    public function isCardPlaceAvailable(string $place): bool
    {
        $info = $this->getCardPlaces()[$place] ?? null;

        return $info && $info['taken'] < $info['limit'];
    }
}
