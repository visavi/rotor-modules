@extends('layout')

@section('title', __('feedback::feedback.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('feedback::feedback.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form method="post" action="{{ route('feedback.send') }}">
            @csrf
            @if ($asksName)
                <div class="mb-3{{ hasError('name') }}">
                    <label for="inputName" class="form-label">{{ __('feedback::feedback.name') }}:</label>
                    <input type="text" class="form-control" id="inputName" name="name" maxlength="100" value="{{ old('name') }}" required>
                    <div class="invalid-feedback">{{ textError('name') }}</div>
                </div>
            @endif

            @if ($asksEmail)
                <div class="mb-3{{ hasError('email') }}">
                    <label for="inputEmail" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="inputEmail" name="email" maxlength="100" value="{{ old('email') }}" required>
                    <div class="invalid-feedback">{{ textError('email') }}</div>
                    <div class="form-text">{{ __('feedback::feedback.email_hint') }}</div>
                </div>
            @endif

            <div class="mb-3{{ hasError('message') }}">
                <label for="message" class="form-label">{{ __('feedback::feedback.message') }}:</label>
                <textarea class="form-control tiptap" id="message" rows="5" name="message" required>{{ old('message') }}</textarea>
                <div class="invalid-feedback">{{ textError('message') }}</div>
            </div>

            {{ getCaptcha() }}

            <button class="btn btn-primary">{{ __('main.send') }}</button>
        </form>
    </div>
@stop
