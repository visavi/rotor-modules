@php
    use Modules\Game\Http\Controllers\DiceController;

    // Кубик крутится лентой случайных граней и останавливается на выпавшей.
    // Порядок бросков задаёт --step, само движение делает CSS
    $faces = 10;
    $dices = $game['dices'] ?? ['user' => [6, 6], 'banker' => [6, 6]];

    $strip = static function (int $face) use ($faces, $game) {
        $strip = [];

        if ($game) {
            for ($i = 0; $i < $faces; $i++) {
                $strip[] = random_int(1, DiceController::FACES);
            }
        }

        $strip[] = $face;

        return $strip;
    };

    $step = 0;
@endphp

<div id="dices-table">
    @foreach (['banker', 'user'] as $side)
        <b>{{ __('game::games.dices_' . ($side === 'user' ? 'your' : 'banker')) }}</b><br>

        <div class="dices-row mb-3 @if ($game) dices-rolling @endif">
            @foreach ($dices[$side] as $face)
                @php $line = $strip($face); @endphp

                <div class="dices-cup" style="--step: {{ $step++ }}">
                    <div class="dices-strip" style="--stop: {{ count($line) - 1 }}">
                        @foreach ($line as $value)
                            <img class="dices-face" src="/assets/modules/games/dices/{{ $value }}.svg" alt="{{ $value }}">
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($game)
                <span class="dices-sum dices-late">{{ $game['scores'][$side] }}</span>
            @endif
        </div>
    @endforeach

    @if ($game)
        {{-- Итог ждёт последний кубик, иначе он известен раньше броска --}}
        <div class="fw-bold mb-3 dices-late">
            @if ($game['result'] === 'victory')
                <span class="text-success"><i class="fas fa-trophy"></i> {{ __('game::games.victory') }}</span><br>
                {{ __('game::games.win_amount', ['money' => plural(DiceController::WIN, setting('moneyname'))]) }}
            @elseif ($game['result'] === 'lost')
                <span class="text-danger">{{ __('game::games.lost') }}</span><br>
                {{ __('game::games.bj_lost', ['money' => plural(DiceController::BET, setting('moneyname'))]) }}
            @else
                {{ __('game::games.draw') }}<br>
                {{ __('game::games.bj_returned', ['money' => plural(DiceController::BET, setting('moneyname'))]) }}
            @endif
        </div>
    @endif

    <form action="/games/dices/roll" method="post" class="mb-3" data-ajax data-ajax-replace="#dices-table" data-ajax-swap="outer">
        @csrf
        <button type="submit" class="btn btn-primary">{{ __('game::games.play') }}</button>
    </form>

    @if ($game)
        <div class="dices-balance">
            <span class="dices-balance-old">{{ __('game::games.balance', ['money' => plural($game['before'], setting('moneyname'))]) }}</span>
            <span class="dices-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
