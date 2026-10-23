@extends('layout')

@section('title', __('payment::payments.balance.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.balance.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section mb-3 shadow">
        <div class="section-body">
            <div>{{ __('payment::payments.balance.current') }}: <b>{{ plural(auth()->user()?->money, setting('moneyname')) }}</b></div>
            <div class="text-muted">{{ __('payment::payments.balance.rate', ['money' => plural($rate, setting('moneyname')), 'currency' => setting('currency')]) }}</div>
        </div>
    </div>

    <div class="section-form mb-3 shadow">
        <form class="js-balance-form" action="/payments/balance" method="post" data-rate="{{ $rate }}" data-forms="{{ setting('moneyname') }}">
            @csrf
            <div class="mb-3">
                <label for="amount" class="form-label">{{ __('payment::payments.balance.amount', ['currency' => setting('currency')]) }}:</label>
                <input class="form-control{{ hasError('amount') }}" id="amount" name="amount" type="number" value="{{ old('amount', $amount) }}" min="{{ $min }}" max="{{ $max }}" step="1" required>
                <div class="invalid-feedback">{{ textError('amount') }}</div>
                <div class="form-text">{{ __('payment::payments.balance.amount_hint', ['min' => $min, 'max' => number_format($max, 0, ',', ' '), 'currency' => setting('currency')]) }}</div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input class="form-control{{ hasError('email') }}" id="email" name="email" type="text" value="{{ old('email', auth()->user()?->email) }}" maxlength="100" required>
                <div class="invalid-feedback">{{ textError('email') }}</div>
            </div>

            <div class="mb-3">
                <span class="fw-bold">{{ __('payment::payments.balance.you_get') }}:</span>
                <span class="h5 text-primary" id="js-balance-coins">{{ plural((int) old('amount', $amount) * $rate, setting('moneyname')) }}</span>
            </div>

            <button class="btn btn-primary"><i class="fas fa-credit-card"></i> {{ __('payment::payments.proceed_payment') }}</button>
        </form>
    </div>
@stop

@push('scripts')
    <script type="module">
        const form = document.querySelector('.js-balance-form');
        const rate = parseInt(form.dataset.rate, 10);
        const forms = form.dataset.forms.split(',').map(form => form.trim());
        const coins = document.getElementById('js-balance-coins');

        // Как plural() в helpers.php: разряды через узкий пробел, форма слова по числу
        function plural(num) {
            const n = Math.abs(num) % 100;
            const index = n % 10 === 1 && n !== 11 ? 0
                : n % 10 > 1 && n % 10 < 5 && (n < 12 || n > 14) ? 1 : 2;

            return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, '\u202F') + ' ' + (forms[index] ?? forms[0]);
        }

        form.querySelector('#amount').addEventListener('input', function () {
            coins.textContent = plural((parseInt(this.value, 10) || 0) * rate);
        });
    </script>
@endpush
