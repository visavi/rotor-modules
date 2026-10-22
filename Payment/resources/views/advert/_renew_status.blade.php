{{-- Срок продлеваемой рекламы: $advert — ссылка или карточка --}}
@if ($advert->isActive())
    <div class="alert alert-success">
        <div>{{ $advert->getPlaceName() }}. {{ __('payment::payments.paid_adverts.renew_shown', ['date' => dateFixed($advert->deleted_at)]) }}</div>
        <div>{{ __('payment::payments.paid_adverts.renew_active') }}</div>
    </div>
@else
    <div class="alert alert-warning">
        <div>{{ $advert->getPlaceName() }}. {{ __('payment::payments.paid_adverts.renew_expired_at', ['date' => dateFixed($advert->deleted_at)]) }}</div>
        <div>{{ __('payment::payments.paid_adverts.renew_expired') }}</div>
    </div>
@endif
