{{-- Реклама в «Моей рекламе», активная или истёкшая: $advert — ссылка или карточка --}}
@if ($advert->isCard())
    @include('payment::card/_card', ['card' => $advert->cardData(), 'layout' => 'feed', 'footer' => 'payment::advert/_my_card_footer'])
@else
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fas fa-globe-americas"></i>
            <a href="{{ $advert->url }}" target="_blank" rel="nofollow">{{ $advert->names[0] }}</a>
            @if (count($advert->names) > 1)
                <span class="badge bg-info">{{ count($advert->names) }}</span>
            @endif
            <div class="float-end">
                @if ($advert->isActive())
                    <a href="/payments/my/edit/{{ $advert->id }}" title="{{ __('main.edit') }}"><i class="fas fa-pencil-alt text-muted"></i></a>
                @endif
                <a class="ms-2" href="{{ $advert->renewUrl() }}" title="{{ __('payment::payments.paid_adverts.renew') }}"><i class="fas fa-rotate-right text-muted"></i></a>
            </div>
        </div>

        <div class="section-body">
            <div>{{ __('payment::payments.paid_adverts.place') }}: <b>{{ $advert->getPlaceName() }}</b></div>
            <div>
                {{ __('payment::payments.paid_adverts.' . ($advert->isActive() ? 'expires' : 'expired')) }}:
                <small class="section-date text-muted fst-italic">{{ dateFixed($advert->deleted_at) }}</small>
            </div>
        </div>
    </div>
@endif
