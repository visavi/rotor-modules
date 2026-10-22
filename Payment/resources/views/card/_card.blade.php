{{-- Рекламная карточка: $card — массив title, text, url, image; $layout — feed (картинка слева) или sidebar (картинка сверху);
     $footer — необязательный партиал под содержимым ленточной карточки (срок и действия владельца) --}}
@php
    $host = parse_url($card['url'], PHP_URL_HOST) ?: $card['url'];
@endphp

@if ($layout === 'sidebar')
    <div class="small text-break">
        <div class="text-muted">{{ __('payment::payments.cards.advert') }}</div>
        @if ($card['image'])
            <a href="{{ $card['url'] }}" target="_blank" rel="nofollow sponsored">
                <img src="{{ $card['image'] }}" class="img-fluid rounded my-1" alt="{{ $card['title'] }}" loading="lazy">
            </a>
        @endif
        <a class="fw-bold d-block" href="{{ $card['url'] }}" target="_blank" rel="nofollow sponsored">{{ $card['title'] }}</a>
        @if ($card['text'])
            <div>{!! \Modules\Payment\Models\PaidAdvert::cardTextHtml($card['text'], 100) !!}</div>
        @endif
        <div class="text-muted"><i class="fas fa-link"></i> {{ $host }}</div>
    </div>
@else
    <div class="section mb-3 shadow">
        <div class="small text-muted mb-1">{{ __('payment::payments.cards.advert') }}</div>
        <div class="d-flex gap-3 align-items-center">
            @if ($card['image'])
                {{-- На телефоне 96px, на широком экране до 140px: своего CSS у модуля нет --}}
                <a class="flex-shrink-0" href="{{ $card['url'] }}" target="_blank" rel="nofollow sponsored">
                    <img src="{{ $card['image'] }}" class="rounded" width="140" height="140" style="width: clamp(96px, 15vw, 140px); height: auto" alt="{{ $card['title'] }}" loading="lazy">
                </a>
            @endif
            <div class="flex-grow-1 text-break">
                <a class="fw-bold fs-5" href="{{ $card['url'] }}" target="_blank" rel="nofollow sponsored">{{ $card['title'] }}</a>
                @if ($card['text'])
                    <div>{!! \Modules\Payment\Models\PaidAdvert::cardTextHtml($card['text']) !!}</div>
                @endif
                <div class="small text-muted"><i class="fas fa-link"></i> {{ $host }}</div>
            </div>
        </div>

        @isset($footer)
            <div class="border-top mt-2 pt-2">
                @include($footer)
            </div>
        @endisset
    </div>
@endif
