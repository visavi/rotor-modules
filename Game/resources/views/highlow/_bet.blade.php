<div class="section-form mb-3 shadow">
    <form action="/games/highlow/bet" method="post">
        @csrf

        <div class="mb-3{{ hasError('bet') }}">
            <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
            <input class="form-control" name="bet" id="bet" value="{{ $bet }}" required>
            <div class="invalid-feedback">{{ textError('bet') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('game::games.play') }}</button>
    </form>
</div>
