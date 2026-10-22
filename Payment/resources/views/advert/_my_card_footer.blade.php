{{-- Срок и действия владельца под карточкой в «Моей рекламе»: $advert — карточка --}}
<div class="d-flex justify-content-between gap-2">
    <div>
        {{ __('payment::payments.paid_adverts.place') }}: <b>{{ $advert->getPlaceName() }}</b>,
        {{ __('payment::payments.paid_adverts.' . ($advert->isActive() ? 'expires' : 'expired')) }}:
        <small class="section-date text-muted fst-italic">{{ dateFixed($advert->deleted_at) }}</small>
    </div>
    <div class="text-nowrap">
        @if ($advert->isActive())
            <a href="/payments/my/card/{{ $advert->id }}" title="{{ __('main.edit') }}"><i class="fas fa-pencil-alt text-muted"></i></a>
        @endif
        <a class="ms-2" href="{{ $advert->renewUrl() }}" title="{{ __('payment::payments.paid_adverts.renew') }}"><i class="fas fa-rotate-right text-muted"></i></a>
    </div>
</div>
