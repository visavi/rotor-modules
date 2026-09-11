@extends('layout')

@section('title', __('gift::gifts.send_gift'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active"><a href="/gifts">{{ __('gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('gift::gifts.send_gift') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form action="/gifts/send/{{ $gift->id }}" method="post">
            @csrf
            @php $selected = old('users', $user ? [$user->login] : []); @endphp

            <div class="mb-3{{ hasError('users') }}">
                <label for="users" class="form-label">{{ __('gift::gifts.recipients') }}:</label>
                <select class="form-select input-user" id="users" name="users[]" multiple data-server="{{ route('search-users') }}" data-value-field="login" data-label-field="login" data-max="{{ $maxUsers }}" data-placeholder="{{ __('main.user_login') }}">
                    @foreach ($selected as $login)
                        <option value="{{ $login }}" selected>{{ $login }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('users') }}</div>
            </div>

            <div class="mb-3{{ hasError('msg') }}">
                <label for="msg" class="form-label">{{ __('main.message') }}:</label>
                <textarea class="form-control tiptap" maxlength="1000" id="msg" rows="5" name="msg" placeholder="{{ __('main.message') }}">{{ old('msg') }}</textarea>
                <div class="invalid-feedback">{{ textError('msg') }}</div>
                <span class="js-textarea-counter"></span>
            </div>

            <div class="mb-3">
                <a href="/gifts/send/{{ $gift->id }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                {{ __('gift::gifts.price') }}: <span class="badge bg-primary"><span id="total-price">{{ $gift->price }}</span> {{ setting('currency') }}</span>
            </div>

            <button class="btn btn-primary">{{ __('main.send') }}</button>
        </form>
    </div>
@stop

@push('scripts')
    <script type="module">
        const price = {{ $gift->price }};
        const users = document.getElementById('users');
        const total = document.getElementById('total-price');

        // Теги шлют change на исходном select, пересчитываем сумму за всех получателей
        // Пока получатели не выбраны, показываем цену одного подарка, а не 0
        const recount = () => total.textContent = String(price * (users.selectedOptions.length || 1));

        recount();
        users.addEventListener('change', recount);
    </script>
@endpush
