@php
    $pending = $game && ! isset($game['result']);
    $shown = $game['shown'] ?? 0;
    $cards = $game['cards'] ?? [];

    // Анимируется только карта этого хода, лежавшие раньше остаются на месте
    $last = count($cards) - 1;
    $fresh = $shown && $last >= $shown;
@endphp

<div id="hl-table">
    @if ($game)
        <div class="hl-cards mb-3">
            @foreach ($cards as $index => $card)
                <img class="hl-card{{ $fresh && $index === $last ? ' hl-deal' : '' }}" src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
            @endforeach
        </div>

        @if ($pending)
            @if (! empty($game['draw']))
                <div class="mb-2">{{ __('game::games.hl_draw') }}</div>
            @endif

            <div class="mb-3">
                {{ __('game::games.hl_multiplier', ['multiplier' => $game['multiplier']]) }}
                &mdash;
                {{ __('game::games.hl_pot', ['money' => plural((int) ($game['bet'] * $game['multiplier']), setting('moneyname'))]) }}
            </div>

            <div class="hl-actions mb-3">
                @foreach (['higher' => 'btn-success', 'lower' => 'btn-primary'] as $guess => $class)
                    @if ($chances[$guess])
                        <form action="/games/highlow/move" method="post" class="d-inline" data-ajax data-ajax-replace="#hl-table" data-ajax-swap="outer">
                            @csrf
                            <input type="hidden" name="guess" value="{{ $guess }}">
                            <button type="submit" class="btn {{ $class }}">
                                {{ __('game::games.hl_' . $guess) }}
                                <span class="badge text-bg-light">x{{ $chances[$guess . '_payout'] }}</span>
                            </button>
                        </form>
                    @endif
                @endforeach

                @if ($game['multiplier'] > 1)
                    <form action="/games/highlow/cash" method="post" class="d-inline" data-ajax data-ajax-replace="#hl-table" data-ajax-swap="outer">
                        @csrf
                        <button type="submit" class="btn btn-warning">{{ __('game::games.hl_cash') }}</button>
                    </form>
                @endif
            </div>

            {{-- Видно, из чего складывается цена шага: карт старше, младше и равных --}}
            <div class="text-muted small mb-3">
                {{ __('game::games.hl_chances', [
                    'higher' => $chances['higher'],
                    'lower'  => $chances['lower'],
                    'equal'  => $chances['equal'],
                ]) }}
            </div>

            {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
        @else
            {{-- Итог ждёт последнюю карту, иначе он известен раньше, чем она легла --}}
            <div class="fw-bold mb-3 hl-late">
                @if ($game['result'] === 'victory')
                    <span class="text-success">{{ __('game::games.victory') }}</span><br>
                    {{ __('game::games.win_amount', ['money' => plural($game['win'], setting('moneyname'))]) }}
                @else
                    <span class="text-danger">{{ __('game::games.lost') }}</span><br>
                    {{ __('game::games.bj_lost', ['money' => plural($game['bet'], setting('moneyname'))]) }}
                @endif
            </div>

            <div class="hl-balance">
                <span class="hl-balance-old">{{ __('game::games.balance', ['money' => plural($game['before'] - $game['bet'], setting('moneyname'))]) }}</span>
                <span class="hl-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
            </div>

            {{-- Ставка подставлена прежняя, но её можно поменять прямо здесь --}}
            <div class="hl-late mt-3">
                @include('game::highlow/_bet', ['bet' => $game['bet']])
            </div>
        @endif
    @else
        @include('game::highlow/_bet', ['bet' => old('bet')])

        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
