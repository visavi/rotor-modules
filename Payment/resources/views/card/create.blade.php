@extends('layout')

@section('title', __('payment::payments.cards.title'))

@section('header')
    @if (getUser())
        <div class="float-end">
            <a class="btn btn-adaptive" href="/payments/my">{{ __('payment::payments.paid_adverts.my_title') }}</a>
        </div>
    @endif

    <h1>{{ __('payment::payments.paid_adverts.create_advert') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.cards.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('payment::advert/_tabs')

    @if (! getUser())
        <div class="alert alert-info">
            {{ __('payment::payments.cards.login_required') }}.
            <a href="{{ route('login') }}">{{ __('payment::payments.cards.login_link') }}</a>
        </div>
    @elseif ($places)
        <div class="section-form mb-3 shadow">
            <form class="js-card-form" action="/payments/card/calculate" method="post" enctype="multipart/form-data"
                  data-prices="{{ json_encode(array_map(static fn ($place) => $place['price'], $places)) }}"
                  data-taken="{{ json_encode(array_map(static fn ($place) => __('payment::payments.cards.taken', ['taken' => $place['taken'], 'limit' => $place['limit']]), $places)) }}">
                @csrf
                <div class="mb-3">
                    <label for="place" class="form-label">{{ __('payment::payments.cards.place') }}:</label>
                    <select class="form-select{{ hasError('place') }}" name="place" id="place">
                        @foreach ($places as $key => $place)
                            <option value="{{ $key }}" @selected(old('place') === $key) @disabled($place['freeAt'])>
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

                <div class="mb-3">
                    <label for="title" class="form-label">{{ __('payment::payments.cards.heading') }}:</label>
                    <input class="form-control{{ hasError('title') }}" id="title" name="title" type="text" value="{{ old('title') }}" minlength="5" maxlength="60" required>
                    <div class="invalid-feedback">{{ textError('title') }}</div>
                </div>

                <div class="mb-3">
                    <label for="text" class="form-label">{{ __('payment::payments.cards.text') }}:</label>
                    <textarea class="form-control{{ hasError('text') }}" id="text" name="text" rows="3" maxlength="200">{{ old('text') }}</textarea>
                    <div class="invalid-feedback">{{ textError('text') }}</div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">{{ __('payment::payments.cards.image') }}:</label>
                    <input class="form-control{{ hasError('image') }}" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
                    <div class="invalid-feedback">{{ textError('image') }}</div>
                    <div class="form-text">{{ __('payment::payments.cards.image_hint') }}</div>
                </div>

                <div class="mb-3">
                    <label for="url" class="form-label">{{ __('payment::payments.cards.url') }}:</label>
                    <input class="form-control{{ hasError('url') }}" id="url" name="url" type="text" value="{{ old('url') }}" maxlength="100" placeholder="https://" required>
                    <div class="invalid-feedback">{{ textError('url') }}</div>
                </div>

                <div class="col-sm-4 mb-3">
                    <label for="term" class="form-label">{{ __('payment::payments.paid_adverts.term') }}:</label>
                    <input class="form-control{{ hasError('term') }}" type="number" name="term" id="term" value="{{ old('term', 10) }}" min="1" max="365" required>
                    <div class="invalid-feedback">{{ textError('term') }}</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input class="form-control{{ hasError('email') }}" id="email" name="email" type="text" value="{{ old('email', getUser('email')) }}" maxlength="100" required>
                    <div class="invalid-feedback">{{ textError('email') }}</div>
                </div>

                <div class="mb-3">
                    <span class="fw-bold">{{ __('payment::payments.total_paid') }}:</span>
                    <span class="h5 text-primary"><span id="js-card-total">0</span> {{ setting('currency') }}</span>
                </div>

                <button class="btn btn-primary">{{ __('payment::payments.place_order') }}</button>
            </form>
        </div>
    @else
        <div class="alert alert-info">{{ __('payment::payments.cards.no_places') }}</div>
    @endif
@stop

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
