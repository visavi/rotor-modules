@php
    use Modules\Game\Http\Controllers\GuessNumberController as Guess;

    $result = $game['result'] ?? null;
    $history = $game['history'] ?? [];
    $last = $history ? array_key_last($history) : null;

    // Границы после каждой попытки: шкала сужается на глазах,
    // поэтому нужны и прежние границы — от них уезжает анимация
    $low = Guess::MIN;
    $high = Guess::MAX;
    $from = [$low, $high];

    // Число за краем шкалы попытку сжигает, но границы двигать не должно
    $clamp = static fn (int $value): int => max(Guess::MIN, min(Guess::MAX, $value));

    foreach ($history as $attempt) {
        $from = [$low, $high];

        if ($attempt['hint'] === 'more') {
            $low = max($low, $clamp($attempt['number'] + 1));
        }

        if ($attempt['hint'] === 'less') {
            $high = min($high, $clamp($attempt['number'] - 1));
        }
    }

    $span = Guess::MAX - Guess::MIN + 1;
    $offset = static fn (int $value): float => round(($clamp($value) - Guess::MIN) / $span * 100, 2);
    $width = static fn (int $from, int $to): float => round(($to - $from + 1) / $span * 100, 2);
@endphp

<div id="guess-box">
    <div class="guess-scale mb-2">
        <div class="guess-band" style="--left: {{ $offset($low) }}%; --width: {{ $width($low, $high) }}%; --from-left: {{ $offset($from[0]) }}%; --from-width: {{ $width($from[0], $from[1]) }}%"></div>

        @foreach ($history as $index => $attempt)
            <span class="guess-tick guess-tick-{{ $attempt['hint'] }} @if ($index === $last) guess-fresh @endif" style="left: {{ min(100, $offset($attempt['number']) + 50 / $span) }}%"></span>
        @endforeach
    </div>

    <div class="guess-range mb-3">
        <span>{{ Guess::MIN }}</span>
        <span class="guess-narrowed">{{ __('game::games.guess_range', ['low' => $low, 'high' => $high]) }}</span>
        <span>{{ Guess::MAX }}</span>
    </div>

    @if ($history)
        <b>{{ __('game::games.guess_history') }}</b>

        <div class="guess-history mb-3">
            @foreach ($history as $index => $attempt)
                <div class="guess-line guess-line-{{ $attempt['hint'] }} @if ($index === $last) guess-reveal @endif">
                    <span class="guess-number">{{ $attempt['number'] }}</span>

                    @if ($attempt['hint'] === 'exact')
                        <i class="fas fa-check"></i> {{ __('game::games.guess_hint_exact') }}
                    @elseif ($attempt['hint'] === 'less')
                        <i class="fas fa-arrow-down"></i> {{ __('game::games.guess_hint_less') }}
                    @else
                        <i class="fas fa-arrow-up"></i> {{ __('game::games.guess_hint_more') }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($result === 'won')
        <div class="fw-bold mb-3 guess-late">
            <span class="text-success"><i class="fas fa-trophy"></i> {{ __('game::games.guess_congratulations', ['number' => $game['number']]) }}</span><br>
            {{ __('game::games.win_amount', ['money' => plural($game['prize'], setting('moneyname'))]) }}
        </div>
    @elseif ($result === 'lost')
        <div class="fw-bold mb-3 guess-late">
            <span class="text-danger">{{ __('game::games.guess_defeat') }}</span><br>
            {{ __('game::games.guess_number_was', ['number' => $game['number']]) }}
        </div>
    @endif

    <div class="section-form mb-3 shadow">
        <form action="/games/guess/go" method="post" data-ajax data-ajax-replace="#guess-box" data-ajax-swap="outer">
            @csrf

            @if ($game && $result === null)
                <div class="mb-2">{{ __('game::games.guess_attempts_left', ['count' => $game['try']]) }}</div>
            @else
                <div class="mb-2">{{ __('game::games.guess_attempt_price', ['money' => plural($price, setting('moneyname'))]) }}</div>
            @endif

            <div class="mb-3{{ hasError('guess') }}">
                <label for="guess" class="form-label">{{ __('game::games.guess_enter') }}</label>
                <input class="form-control guess-input" name="guess" id="guess" type="text" inputmode="numeric" value="" required>
                <div class="invalid-feedback">{{ textError('guess') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.guess_button') }}</button>
        </form>
    </div>

    @if ($result)
        <div class="guess-balance">
            <span class="guess-balance-old">{{ __('game::games.balance', ['money' => plural($game['before'], setting('moneyname'))]) }}</span>
            <span class="guess-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif

    @if ($game && $result === null)
        <form action="/games/guess/reset" method="post" data-ajax data-ajax-replace="#guess-box" data-ajax-swap="outer" class="mt-2">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">{{ __('game::games.guess_restart') }}</button>
        </form>
    @endif
</div>
