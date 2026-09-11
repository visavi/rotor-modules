@php
    use Modules\Game\Http\Controllers\BanditController;

    // Барабан прокручивает случайные символы и останавливается на выпавших.
    // Лента и остановка считаются здесь, движение — в CSS, скрипт игре не нужен
    $filler = 12;
    $cells = $spin['cells'] ?? [1 => 1, 2 => 2, 3 => 3, 4 => 8, 5 => 8, 6 => 8, 7 => 5, 8 => 6, 9 => 7];

    $highlight = [];
    foreach ($spin['results'] ?? [] as $result) {
        foreach ($result['line'] as $cell) {
            $highlight[$cell] = true;
        }
    }

    $reels = [];
    foreach ([1, 2, 3] as $column) {
        $strip = [];

        if ($spin) {
            for ($i = 0; $i < $filler; $i++) {
                $strip[] = random_int(1, BanditController::SYMBOLS);
            }
        }

        foreach ([0, 1, 2] as $row) {
            $strip[] = $cells[$row * 3 + $column];
        }

        $reels[$column] = $strip;
    }
@endphp

<div id="bandit-machine">
    <div class="bandit-reels mb-3 @if ($spin) bandit-spinning @endif">
        @foreach ($reels as $column => $strip)
            <div class="bandit-reel">
                {{-- Лента останавливается так, чтобы в окне остались три последних символа --}}
                <div class="bandit-strip" style="--stop: {{ count($strip) - 3 }}; --reel: {{ $column }}">
                    @foreach ($strip as $index => $symbol)
                        @php $cell = $index - count($strip) + 3; @endphp

                        <div class="bandit-cell @if ($spin && $cell >= 0 && isset($highlight[$cell * 3 + $column])) bandit-hit @endif">
                            <img src="/assets/modules/games/bandit/{{ $symbol }}.svg" alt="{{ __('game::games.symbols.' . BanditController::NAMES[$symbol]) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($spin)
        {{-- Итог ждёт последний барабан, иначе выигрыш известен раньше остановки --}}
        <div class="fw-bold bandit-late mb-3">
            @if ($spin['sum'] > 0)
                @foreach ($spin['results'] as $result)
                    {{ $result['text'] }}<br>
                @endforeach

                <span class="text-success">
                    <i class="fas fa-trophy"></i> {{ __('game::games.win_amount', ['money' => plural($spin['sum'], setting('moneyname'))]) }}
                </span>
            @else
                <span class="text-danger">{{ __('game::games.lost') }}</span>
            @endif
        </div>
    @endif

    <form action="/games/bandit/spin" method="post" class="mb-3" data-ajax data-ajax-replace="#bandit-machine" data-ajax-swap="outer">
        @csrf
        <button type="submit" class="btn btn-primary">{{ __('game::games.play') }}</button>
    </form>

    @if ($spin)
        <div class="bandit-balance">
            <span class="bandit-balance-old">{{ __('game::games.balance', ['money' => plural($spin['before'], setting('moneyname'))]) }}</span>
            <span class="bandit-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
