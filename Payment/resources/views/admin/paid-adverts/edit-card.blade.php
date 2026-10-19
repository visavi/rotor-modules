@extends('layout')

@section('title', $card->exists ? __('payment::payments.cards.edit') : __('main.create'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="/admin/paid-adverts?place={{ $card->place }}">{{ __('payment::payments.paid_adverts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ $card->exists ? __('payment::payments.cards.edit') : __('main.create') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($card->exists)
        <div class="mb-3">
            {{ $card->getPlaceName() }}, <i class="far fa-user"></i> {{ $card->user->getProfile() }}
        </div>
    @else
        @include('payment::admin/paid-adverts/_create_tabs')
    @endif

    <div class="section-form mb-3 shadow">
        <form action="/admin/paid-adverts/{{ $card->exists ? 'edit-card/' . $card->id : 'create-card' }}" method="post" enctype="multipart/form-data">
            @csrf
            @if (! $card->exists)
                <div class="mb-3">
                    <label for="place" class="form-label">{{ __('payment::payments.cards.place') }}:</label>
                    <select class="form-select{{ hasError('place') }}" id="place" name="place">
                        @foreach (\Modules\Payment\Models\PaidAdvert::CARD_PLACES as $placeName)
                            <option value="{{ $placeName }}"{{ old('place', $card->place) === $placeName ? ' selected' : '' }}>{{ __('payment::payments.cards.' . $placeName) }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">{{ textError('place') }}</div>
                </div>
            @endif

            @include('payment::card/_edit_fields')

            <div class="col-sm-4 mb-3">
                <label for="term" class="form-label">{{ __('payment::payments.paid_adverts.expires') }}:</label>
                <input class="form-control{{ hasError('term') }}" type="datetime-local" name="term" id="term" value="{{ old('term', $card->deleted_at?->format('Y-m-d\TH:i')) }}" required>
                <div class="invalid-feedback">{{ textError('term') }}</div>
            </div>

            <button class="btn btn-primary">{{ $card->exists ? __('main.save') : __('main.create') }}</button>
            @include('payment::admin/paid-adverts/_stop_button', ['advert' => $card])
        </form>
    </div>
@stop
