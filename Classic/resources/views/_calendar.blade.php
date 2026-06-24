<div class="calendar">
<div class="calendar-nav">
    <a href="{{ request()->fullUrlWithQuery(['calendar' => $prev]) }}">‹</a>
    <span>{{ $monthLabel }}</span>
    <a href="{{ request()->fullUrlWithQuery(['calendar' => $next]) }}">›</a>
</div>
<div class="calendar-grid">
    <div class="calendar-head text-center">{{ __('main.mo') }}</div>
    <div class="calendar-head text-center">{{ __('main.tu') }}</div>
    <div class="calendar-head text-center">{{ __('main.we') }}</div>
    <div class="calendar-head text-center">{{ __('main.th') }}</div>
    <div class="calendar-head text-center">{{ __('main.fr') }}</div>
    <div class="calendar-head text-center text-danger">{{ __('main.sa') }}</div>
    <div class="calendar-head text-center text-danger">{{ __('main.su') }}</div>

    @foreach ($calendar as $week)
        @foreach ($week as $keyDay => $valDay)
            @if ($currentDay === $valDay)
                <div class="calendar-cell calendar-today">{{ $valDay }}</div>
                @continue
            @endif

            @if (isset($newsIds[$valDay]) && Route::has('news.view'))
                <div class="calendar-cell calendar-news"><a href="{{ route('news.view', ['id' => $newsIds[$valDay]]) }}">{{ $valDay }}</a></div>
                @continue
            @endif

            @if ($keyDay === 5 || $keyDay === 6)
                <div class="calendar-cell text-center text-danger">{{ $valDay ?: '' }}</div>
                @continue
            @endif

            <div class="calendar-cell text-center">{{ $valDay ?: '' }}</div>
        @endforeach
    @endforeach
</div>
</div>