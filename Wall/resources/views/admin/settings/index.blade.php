@extends('layout')

@section('title', __('wall::walls.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('wall::walls.settings') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="section-form mb-3 shadow">
        <form method="post" action="{{ route('wall.settings.update') }}">
            @csrf

            <div class="mb-3{{ hasError('sets[wallpost]') }}">
                <label for="wallpost" class="form-label">{{ __('wall::walls.walls_per_page') }}:</label>
                <input type="number" class="form-control" id="wallpost" name="sets[wallpost]" maxlength="2" value="{{ getInput('sets.wallpost', $settings['wallpost'] ?? 10) }}" required>
                <div class="invalid-feedback">{{ textError('sets[wallpost]') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('main.save') }}</button>
        </form>
    </div>
@stop
