{{-- Номер приходит по клику POST-запросом: в разметке страницы его нет, парсеру нечего собирать --}}
@if ($revealed ?? false)
    <span class="d-inline-flex flex-wrap align-items-center gap-3">
        <a href="tel:{{ $item->phone }}" class="text-decoration-none"><i class="fa-solid fa-phone fs-5 me-2"></i>{{ $item->phone }}</a>

        @foreach ($item->getMessengers() as $messenger)
            @if ($messenger['url'])
                <a href="{{ $messenger['url'] }}" class="text-decoration-none fs-4 lh-1" target="_blank" rel="noopener" title="{{ $messenger['label'] }}">@include('board::boards/_messenger_icon')</a>
            @else
                <span class="fs-6" title="{{ __('board::boards.messenger_has_number', ['name' => $messenger['label']]) }}">@include('board::boards/_messenger_icon')</span>
            @endif
        @endforeach
    </span>
@else
    <a href="#" role="button" class="{{ ($button ?? false) ? 'btn btn-success' : 'text-decoration-none' }}" data-ajax data-ajax-url="{{ route('items.phone', ['id' => $item->id]) }}" data-ajax-replace="self" data-ajax-swap="outer">
        <i class="fa-solid fa-phone{{ ($button ?? false) ? ' me-1' : ' fs-5 me-2' }}"></i>{{ __('board::boards.show_phone') }}
    </a>
@endif
