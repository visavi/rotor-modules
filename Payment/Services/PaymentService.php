<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        private readonly CardImageService $cardImageService,
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
     * Создаёт оплаченную рекламную карточку или продлевает существующую
     *
     * Карточка встаёт на сайт сразу, без проверки: админы узнают о ней
     * из уведомления и снимают нарушителя сами. При продлении уведомление
     * приходит, только если сменили картинку — как при правке
     *
     * В данных продления картинка — только новая: старую берём из записи
     * в момент оплаты, путь из расчёта мог устареть после правки
     */
    public function createCard(Order $order): PaidAdvert
    {
        $data = $order->data;

        $attributes = [
            'place'   => $data['place'],
            'url'     => $data['url'],
            'names'   => [$data['title']],
            'text'    => $data['text'],
            'comment' => $data['description'] . ' #' . $order->id,
        ];

        $card = $this->findRenewed($order);

        if ($card) {
            $oldImage = $card->image;

            $card->update([
                ...$attributes,
                'image'      => $data['image'] ?? (empty($data['remove_image']) ? $oldImage : null),
                'deleted_at' => $this->renewEnd($card, $data),
            ]);

            $this->cardImageService->delete($oldImage);

            clearCache('paidCards');

            if ($data['image']) {
                $this->notifyBosses($card, 'cards.notify_image');
            }

            return $card;
        }

        $card = PaidAdvert::query()->create([
            ...$attributes,
            'user_id'    => $order->user_id,
            'image'      => $data['image'],
            'deleted_at' => now()->addDays((int) $data['term']),
        ]);

        clearCache('paidCards');

        $this->notifyBosses($card, 'cards.notify_new');

        return $card;
    }

    /**
     * Продлеваемая заказом реклама покупателя
     *
     * Запись могли удалить, пока шла оплата: тогда заказ создаёт новую, деньги уже списаны.
     * Без покупателя продлевать нечего — where с null стал бы whereNull
     */
    private function findRenewed(Order $order): ?PaidAdvert
    {
        $id = (int) ($order->data['renew_id'] ?? 0);

        if (! $id || ! $order->user_id) {
            return null;
        }

        $query = PaidAdvert::query()->where('user_id', $order->user_id);

        if ($order->type === Order::TYPE_CARD) {
            $query->whereIn('place', PaidAdvert::CARD_PLACES);
        } else {
            $query->whereNotIn('place', PaidAdvert::CARD_PLACES);
        }

        return $query->find($id);
    }

    /**
     * Конец срока после продления
     *
     * Остаток срока активной рекламы не пропадает, а пересчитывается по цене
     * новых опций: 9 дней по 35 с добавленным цветом за 3 станут 9 × 35 / 38 ≈ 8,3 дня.
     * Иначе за день с жирным шрифтом жирным стал бы весь остаток. С прежними опциями
     * цена та же — продление просто от конца срока. Цены текущие для обеих сторон:
     * тарифы могли смениться после покупки
     */
    public function renewEnd(PaidAdvert $advert, array $data): CarbonImmutable
    {
        $end = CarbonImmutable::now()->addDays((int) $data['term']);

        if (! $advert->isActive()) {
            return $end;
        }

        $left = $advert->deleted_at->getTimestamp() - now()->getTimestamp();
        $oldPrice = $this->dailyPrice($this->pricingData($advert));
        $newPrice = $this->dailyPrice($data);

        // Бесплатное место (цена 0) пересчитать не через что — остаток как есть
        if ($oldPrice > 0 && $newPrice > 0) {
            $left = intdiv($left * $oldPrice, $newPrice);
        }

        return $end->addSeconds($left);
    }

    /**
     * Цена суток рекламы с опциями из данных заказа
     */
    private function dailyPrice(array $data): int
    {
        $data['term'] = 1;

        $prices = ($data['type'] ?? null) === Order::TYPE_CARD
            ? $this->calculateCard($data)
            : $this->calculateAdvert($data);

        return (int) $prices['total'];
    }

    /**
     * Платные опции рекламы в виде данных заказа — для расчёта цены
     */
    private function pricingData(PaidAdvert $advert): array
    {
        if ($advert->isCard()) {
            return ['type' => Order::TYPE_CARD, 'place' => $advert->place];
        }

        return [
            'type'  => Order::TYPE_ADVERT,
            'place' => $advert->place,
            'names' => $advert->names,
            'color' => $advert->color,
            'bold'  => $advert->bold,
        ];
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
     * Создаёт оплаченную рекламную ссылку или продлевает существующую
     *
     * Комментарий покупателя (до 255 символов) вместе с описанием заказа
     * не влезает в колонку на 191: строгий MySQL уронил бы уже оплаченный заказ
     */
    public function createAdvert(Order $order): PaidAdvert
    {
        $data = $order->data;

        $attributes = [
            'place'   => $data['place'],
            'url'     => $data['url'],
            'names'   => $data['names'],
            'color'   => $data['color'],
            'bold'    => $data['bold'],
            'comment' => Str::limit($data['description'] . ' #' . $order->id . ' ' . $data['comment'], 191, ''),
        ];

        $advert = $this->findRenewed($order);

        if ($advert) {
            $advert->update([
                ...$attributes,
                'deleted_at' => $this->renewEnd($advert, $data),
            ]);
        } else {
            $advert = PaidAdvert::query()->create([
                ...$attributes,
                'user_id'    => $order->user_id,
                'deleted_at' => now()->addDays((int) $data['term']),
            ]);
        }

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
     * freeAt — когда освободится ближайшее, если место занято целиком.
     * Продлеваемая карточка ($exceptId) своё место не занимает
     *
     * @return array<string, array{name: string, price: int, taken: int, limit: int, freeAt: ?CarbonImmutable}>
     */
    public function getCardPlaces(?int $exceptId = null): array
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
                ->when($exceptId, static fn ($query) => $query->whereKeyNot($exceptId))
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

        if (! $this->isCardPlaceAvailable((string) $data['place'], $data['renew_id'] ?? null)) {
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
    public function isCardPlaceAvailable(string $place, ?int $exceptId = null): bool
    {
        $info = $this->getCardPlaces($exceptId)[$place] ?? null;

        return $info && $info['taken'] < $info['limit'];
    }
}
