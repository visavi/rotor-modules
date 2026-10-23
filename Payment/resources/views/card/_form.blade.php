{{-- Форма покупки карточки: $card — новая или продлеваемая (тогда её данные подставлены), $places — места с ценой и занятостью --}}
<form class="js-card-form" action="/payments/card/calculate" method="post" enctype="multipart/form-data"
      data-prices="{{ json_encode(array_map(static fn ($place) => $place['price'], $places)) }}"
      data-taken="{{ json_encode(array_map(static fn ($place) => __('payment::payments.cards.taken', ['taken' => $place['taken'], 'limit' => $place['limit']]), $places)) }}">
    @csrf
    @if ($card->exists)
        <input type="hidden" name="renew_id" value="{{ $card->id }}">
    @endif

    <div class="mb-3">
        <label for="place" class="form-label">{{ __('payment::payments.cards.place') }}:</label>
        <select class="form-select{{ hasError('place') }}" name="place" id="place">
            @foreach ($places as $key => $place)
                <option value="{{ $key }}" @selected(old('place', $card->place) === $key) @disabled($place['freeAt'])>
                    {{ $place['name'] }} &mdash; {{ $place['price'] }} {{ setting('currency') }} {{ __('payment::payments.paid_adverts.per_day') }}
                    @if ($place['freeAt'])
                        ({{ __('payment::payments.cards.place_full', ['date' => dateFixed($place['freeAt'])]) }})
                    @endif
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">{{ textError('place') }}</div>
        <div class="form-text" id="js-card-taken"></div>
    </div>

    @include('payment::card/_edit_fields')

    <div class="col-sm-4 mb-3">
        <label for="term" class="form-label">{{ __('payment::payments.paid_adverts.term') }}:</label>
        <input class="form-control{{ hasError('term') }}" type="number" name="term" id="term" value="{{ old('term', 10) }}" min="1" max="365" required>
        <div class="invalid-feedback">{{ textError('term') }}</div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email:</label>
        <input class="form-control{{ hasError('email') }}" id="email" name="email" type="text" value="{{ old('email', auth()->user()?->email) }}" maxlength="100" required>
        <div class="invalid-feedback">{{ textError('email') }}</div>
    </div>

    <div class="mb-3">
        <span class="fw-bold">{{ __('payment::payments.total_paid') }}:</span>
        <span class="h5 text-primary"><span id="js-card-total">0</span> {{ setting('currency') }}</span>
    </div>

    <button class="btn btn-primary">{{ __('payment::payments.place_order') }}</button>
</form>

@push('scripts')
    <script type="module">
        const form = document.querySelector('.js-card-form');

        if (form) {
            const prices = JSON.parse(form.dataset.prices);
            const taken = JSON.parse(form.dataset.taken);
            const total = document.getElementById('js-card-total');
            const update = () => {
                const place = form.querySelector('#place').value;
                const term = parseInt(form.querySelector('#term').value, 10) || 0;

                total.textContent = term * (prices[place] || 0);
                document.getElementById('js-card-taken').textContent = taken[place] || '';
            };

            form.addEventListener('input', update);
            update();
        }
    </script>
@endpush
