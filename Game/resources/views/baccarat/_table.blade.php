@php
    // Пока игрок решает, рука банкира закрыта: её вскрытие — отдельный шаг
    $pending = $game && ! isset($game['result']);

    // Карты, показанные до решения, второй раз не анимируются
    $shown = $game['shown'] ?? ['player' => 0, 'banker' => 0];

    $steps = [];
    $step = 0;

    if ($game) {
        // Раздают по кругу игроку и банкиру, третью карту первым берёт игрок.
        // Третья карта банкира ложится уже после вскрытия, поэтому идёт отдельно
        foreach ([0, 1, 2] as $index) {
            foreach (['player', 'banker'] as $side) {
                if ($side === 'banker' && $index === 2) {
                    continue;
                }

                if (isset($game[$side][$index]) && $index >= $shown[$side]) {
                    $steps[$side . $index] = $step++;
                }
            }
        }
    }

    // Рука банкира переворачивается целиком, затем он добирает свою третью карту
    $reveal = $pending ? null : $step++;
    $bankerThird = ! $pending && isset($game['banker'][2]) ? $step++ : null;

    $lastStep = $step;
@endphp

<div id="baccarat-table">
    @if ($game)
        <div class="mb-3">
            @foreach (['banker', 'player'] as $side)
                <div class="mb-2">
                    <div>{{ __('game::games.baccarat_' . $side) }}</div>

                    @foreach ($game[$side] as $index => $card)
                        @if ($side === 'banker' && $index === 2)
                            {{-- Третью карту банкир берёт уже открытой, после вскрытия руки --}}
                            <img class="baccarat-card baccarat-deal" style="--step: {{ $bankerThird }}" src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
                        @elseif ($side === 'banker')
                            {{-- Рубашка ложится сразу, лицо появляется на вскрытии. Пока игрок решает, лица в разметке нет --}}
                            <span class="baccarat-flip @if ($pending) baccarat-closed @endif" @unless ($pending) style="--reveal: {{ $reveal }}" @endunless>
                                <img class="baccarat-back @isset($steps['banker' . $index]) baccarat-deal @endisset" @isset($steps['banker' . $index]) style="--step: {{ $steps['banker' . $index] }}" @endisset src="/assets/modules/games/cards/0.png" alt="image">

                                @unless ($pending)
                                    <img class="baccarat-face" src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
                                @endunless
                            </span>
                        @else
                            <img class="baccarat-card @isset($steps['player' . $index]) baccarat-deal @endisset" @isset($steps['player' . $index]) style="--step: {{ $steps['player' . $index] }}" @endisset src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
                        @endif
                    @endforeach

                    @if (! $pending || $side === 'player')
                        <div class="fw-bold baccarat-late" style="--step: {{ $lastStep }}">
                            {{ __('game::games.baccarat_score', ['score' => $game[$side . '_total']]) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($pending)
            {{-- Решение уходит ajax-ом, стол подменяется целиком, поэтому анимация не рвётся --}}
            <div class="fw-bold mb-3 baccarat-late" style="--step: {{ $lastStep }}">
                {{ __('game::games.baccarat_decision') }}

                <div class="mt-2">
                    <a class="btn btn-success" href="/games/baccarat/decide"
                       data-ajax data-ajax-method="post" data-draw="1"
                       data-ajax-replace="#baccarat-table" data-ajax-swap="outer">{{ __('game::games.bj_take_card') }}</a>
                    {{ __('game::games.bj_or') }}
                    <a class="btn btn-danger" href="/games/baccarat/decide"
                       data-ajax data-ajax-method="post"
                       data-ajax-replace="#baccarat-table" data-ajax-swap="outer">{{ __('game::games.baccarat_stand') }}</a>
                </div>
            </div>
        @else
            <div class="fw-bold mb-3 baccarat-late" style="--step: {{ $lastStep }}">
                {{ __('game::games.baccarat_win_' . $game['result']) }}

                @if ($game['win'] > $game['bet'])
                    <div class="text-success">
                        <i class="fas fa-trophy"></i> {{ __('game::games.win_amount', ['money' => plural($game['win'], setting('moneyname'))]) }}
                    </div>
                @elseif ($game['win'])
                    <div class="text-muted">{{ __('game::games.baccarat_returned') }}</div>
                @else
                    <div class="text-danger">{{ __('game::games.lost') }}</div>
                @endif
            </div>
        @endif
    @endif

    {{-- Пока карты не открыты, баланс показывается на момент до раздачи --}}
    @if ($game)
        <div class="baccarat-balance">
            <span class="baccarat-balance-old" style="--step: {{ $lastStep }}">{{ __('game::games.balance', ['money' => plural($game['before'], setting('moneyname'))]) }}</span>
            <span class="baccarat-late" style="--step: {{ $lastStep }}">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
