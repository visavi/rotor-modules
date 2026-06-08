@extends('layout')

@section('title', __('social_auth::social_auth.complete_title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('social_auth::social_auth.complete_title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form method="post" action="{{ route('social.complete.post') }}">
            @csrf
            <div class="mb-3">
                <p>{{ __('social_auth::social_auth.complete_hint') }}</p>

                <label for="inputEmail" class="form-label">{{ __('users.email') }}:</label>
                <input class="form-control" name="email" type="email" id="inputEmail" maxlength="100" value="{{ old('email') }}" required>
            </div>

            <button class="btn btn-primary">{{ __('main.continue') }}</button>
        </form>
    </div>
@stop
