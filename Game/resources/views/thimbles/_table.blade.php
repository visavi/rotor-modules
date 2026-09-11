@php
    use Modules\Game\Http\Controllers\ThimbleController as Thimble;

    $result = $game['result'] ?? null;

    // Сначала поднимается выбранный напёрсток, следом — тот, где шарик
    $opens = [];

    if ($game) {
        $opens[$game['thimble']] = $game['thimble'] === $game['ball'] ? 'thimble-ball' : 'thimble-up';

        if ($game['thimble'] !== $game['ball']) {
            $opens[$game['ball']] = 'thimble-ball';
        }
    }

    $steps = array_flip(array_keys($opens));

    // Итог появляется после последнего подъёма
    $resultStep = count($opens);
@endphp

<div id="thimbles-box" style="--result-step: {{ $resultStep }}">
    {{-- Кнопки идут без переносов строк, иначе между напёрстками появляются щели --}}
    <form class="thimble-row mb-3" action="/games/thimbles/go" method="post" data-ajax data-ajax-replace="#thimbles-box" data-ajax-swap="outer">
        @csrf
        @foreach (range(1, Thimble::THIMBLES) as $number)
            <button class="thimble-button" name="thimble" value="{{ $number }}" type="submit" aria-label="{{ __('game::games.thimbles_number', ['number' => $number]) }}">@isset($opens[$number])<span class="thimble-stack" style="--step: {{ $steps[$number] }}"><img class="thimble-closed" src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt=""><img class="thimble-open" src="/assets/modules/games/thimbles/{{ $opens[$number] }}.svg" width="86" height="74" alt=""></span>@else<img src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt="">@endisset</button>
        @endforeach
    </form>

    @error('thimble')
        <div class="text-danger mb-2">{{ $message }}</div>
    @enderror

    {{ __('game::games.thimbles_pick') }}<br><br>

    @if ($result)
        <div class="fw-bold mb-2 thimble-late">
            @if ($result === 'won')
                <span class="text-success"><i class="fas fa-trophy"></i> {{ __('game::games.victory') }}</span>
            @else
                <span class="text-danger">{{ __('game::games.lost') }}</span>
            @endif
        </div>

        <div class="thimble-balance">
            <span class="thimble-balance-old">{{ __('game::games.balance', ['money' => plural($game['before'], setting('moneyname'))]) }}</span>
            <span class="thimble-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
