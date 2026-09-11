@php
    use Modules\Game\Http\Controllers\SafeController;

    $result = $game['result'] ?? null;
    $history = $game['history'] ?? [];
    $last = $history ? end($history) : null;

    // Найденные цифры подставляются в форму: переписывать их каждый раз незачем
    $found = [];
    foreach ($last['marks'] ?? [] as $position => $mark) {
        if ($mark !== SafeController::ABSENT && $mark !== SafeController::MOVED) {
            $found[$position] = $mark;
        }
    }
@endphp

<div id="safe-box">
    <div class="safe-door mb-3 @if ($result === 'opened') safe-opened @elseif ($last) safe-shake @endif">
        <img class="safe-image" src="/assets/modules/games/safe/safe-{{ $result === 'opened' ? 'open' : 'closed' }}.svg" alt="{{ __('game::games.safe_alt') }}">
    </div>

    @if ($history)
        <b>{{ __('game::games.safe_attempts') }}</b>

        <div class="safe-history mb-3">
            @foreach ($history as $attempt)
                @php $fresh = $attempt === $last; @endphp

                <div class="safe-line">
                    @foreach ($attempt['marks'] as $position => $mark)
                        {{-- Метки последней попытки открываются по очереди --}}
                        <span class="safe-mark safe-mark-{{ $mark === SafeController::ABSENT ? 'absent' : ($mark === SafeController::MOVED ? 'moved' : 'exact') }} @if ($fresh) safe-reveal @endif" style="--step: {{ $position }}">
                            {{ $mark === SafeController::ABSENT ? $attempt['codes'][$position] : $mark }}
                        </span>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    @if ($result === 'opened')
        <div class="fw-bold mb-3 safe-late">
            <span class="text-success"><i class="fas fa-trophy"></i> {{ __('game::games.safe_success') }}</span><br>
            {{ __('game::games.safe_transferred', ['money' => plural($prize, setting('moneyname'))]) }}
        </div>
    @elseif ($result === 'failed')
        <div class="fw-bold mb-3 safe-late">
            <span class="text-danger">{{ __('game::games.safe_failed') }}</span><br>
            {{ __('game::games.safe_code_was') }}
            @foreach ($game['cipher'] as $digit)
                <span class="safe-mark safe-mark-exact">{{ $digit }}</span>
            @endforeach
        </div>
    @endif

    <div class="section-form mb-3 shadow">
        <form action="/games/safe/go" method="post" data-ajax data-ajax-replace="#safe-box" data-ajax-swap="outer">
            @csrf

            @if ($game && $result === null)
                <div class="mb-2">{{ __('game::games.safe_attempts_left', ['count' => $game['try']]) }}</div>
            @else
                <div class="mb-2">{{ __('game::games.safe_price', ['money' => plural($price, setting('moneyname'))]) }}</div>
            @endif

            <div class="safe-code mb-3{{ hasError('code') }}">
                @foreach (range(0, SafeController::LENGTH - 1) as $position)
                    <input class="form-control safe-digit" name="code{{ $position }}" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1"
                           value="{{ $result === null ? ($found[$position] ?? '') : '' }}" required>
                @endforeach
            </div>

            @error('code')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror

            <button class="btn btn-primary">{{ __('game::games.safe_button') }}</button>
        </form>
    </div>

    @if ($result)
        <div class="safe-balance">
            <span class="safe-balance-old">{{ __('game::games.balance', ['money' => plural($game['before'], setting('moneyname'))]) }}</span>
            <span class="safe-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
