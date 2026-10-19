<?php

use App\Models\User;
use App\Services\DashboardService;
use App\Support\Hook;
use App\Support\Registry;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Services\YooKassaService;

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="/admin/payment-settings">' . __('payment::payments.settings') . '</a>');

// Ссылки в блок администратора
Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="/admin/paid-adverts" class="app-tile">
            <div class="app-tile-icon" style="background:#ffc107"><i class="fas fa-ad"></i></div>
            <div class="app-tile-label">' . __('payment::payments.paid_adverts.title') . '</div>
        </a>
    </div>
    <div class="col">
        <a href="/admin/orders" class="app-tile">
            <div class="app-tile-icon" style="background:#0d6efd"><i class="fas fa-shopping-cart"></i></div>
            <div class="app-tile-label">' . __('payment::payments.orders') . '<span class="badge bg-adaptive app-tile-badge">' . Order::query()->count() . '</span></div>
        </a>
    </div>';
});

// Платная реклама в шаблоне (верх/низ всех страниц), свободный верх продаёт себя сам.
// Приоритет ставит рекламу за рубли выше ссылок за монеты (модуль Advert): при равном
// порядок зависел бы от того, какой модуль установлен раньше
Hook::add('advertTop', static function () {
    $ad = PaidAdvert::renderAdvert(PaidAdvert::TOP_ALL)
        ?? '<i class="fas fa-ad"></i> <a class="small text-muted" href="/payments/advert" rel="nofollow">' . __('payment::payments.paid_adverts.your_advert_here') . '</a>';

    return '<div class="text-center">' . $ad . '</div>';
}, 10);
Hook::add('advertBottom', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::BOTTOM_ALL)) ? '<div class="text-center my-1">' . $ad . '</div>' : '');

// Платная реклама на главной странице
Hook::add('advertIndexTop', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::TOP)) ? '<div class="my-1">' . $ad . '</div>' : '');
Hook::add('advertIndexBottom', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::BOTTOM)) ? '<div class="my-1">' . $ad . '</div>' : '');

// Платная реклама на форуме
Hook::add('advertForum', static fn () => ($ad = PaidAdvert::renderAdvert(PaidAdvert::FORUM)) ? '<div class="my-1">' . $ad . '</div>' : '');

// Рекламная карточка внизу сайдбара, ниже всех остальных хуков. Пустое место продаёт себя само,
// а если карточки в сайдбаре не продаются — прежняя кнопка покупки рекламы
Hook::add('sidebarFooterEnd', static function () {
    if ($card = PaidAdvert::randomCard(PaidAdvert::CARD_SIDEBAR)) {
        return '<li class="mt-3">' . view('payment::card/_card', ['card' => $card, 'layout' => 'sidebar'])->render() . '</li>';
    }

    [$url, $label] = app(PaymentService::class)->getCardPrice(PaidAdvert::CARD_SIDEBAR) > 0
        ? ['/payments/card', __('payment::payments.cards.your_card_here')]
        : ['/payments/advert', __('payment::payments.paid_adverts.site_advert')];

    return '<li class="mt-3 text-center">
        <a class="btn btn-sm btn-adaptive w-100" href="' . $url . '" rel="nofollow"><i class="fas fa-ad"></i> ' . $label . '</a>
    </li>';
}, -100);

// Рекламные карточки в ленте главной: у каждой точки ленты своё место
Hook::add('feedAdvert', static fn (?string $point = null) => ($place = PaidAdvert::CARD_FEED_POINTS[$point] ?? null)
    && ($card = PaidAdvert::randomCard($place))
        ? view('payment::card/_card', ['card' => $card, 'layout' => 'feed'])->render()
        : '');

// Пополнение баланса в своей анкете
Hook::add('userPersonalEnd', static fn () => app(PaymentService::class)->isBalanceEnabled()
    ? view('components.profile.action', [
        'icon'  => 'fas fa-coins',
        'label' => __('payment::payments.balance.title'),
        'url'   => '/payments/balance',
    ])->render()
    : '');

// Виджет платежей на главной админки: считается сумма, а не число заказов
Registry::widget('orders', static fn (int $days): array => [
    'label' => __('payment::payments.payment'),
    'icon'  => 'fas fa-shopping-cart',
    'color' => '#198754',
    'type'  => 'bar',
    'url'   => '/admin/orders',
    'level' => User::BOSS,
    'unit'  => setting('currency'),
    ...DashboardService::trend(
        Order::query()->where('status', YooKassaService::SUCCEEDED),
        $days,
        sum: 'amount',
    ),
]);
