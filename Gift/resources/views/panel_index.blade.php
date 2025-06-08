@extends('layout')

@section('title', __('gift::gifts.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="/admin/modules">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item active">{{ __('gift::gifts.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container">
        @if ($gifts->isNotEmpty())
            <div class="mb-3">
                <form action="/admin/gifts" method="post">
                    @csrf
                    <div class="row mb-3">
                        @foreach ($gifts as $gift)
                            <div class="col-md-2 col-sm-3">
                                <img src="{{ $gift->path }}" alt="{{ $gift->name }}">
                            </div>
                            <div class="col-md-2 col-sm-3">
                                <label for="gift_{{ $gift->id }}" class="form-label">{{ __('gift::gifts.price') }} ({{ setting('currency') }}):</label>
                                <input class="form-control" name="gifts[{{ $gift->id }}]" id="gift_{{ $gift->id }}" maxlength="10" value="{{ $gift->price }}"><br>
                            </div>
                        @endforeach
                    </div>
                    <button class="btn btn-primary">{{ __('main.save') }}</button>
                </form>
            </div>
        @else
            {{ showError(__('gift::gifts.empty_gifts')) }}
        @endif

        {{ $gifts->links() }}
    </div>
@stop
