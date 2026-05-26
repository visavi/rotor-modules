@extends('layout')

@section('title', __('template::template.template'))

@section('header')
    @if (isAdmin('moder'))
        <div class="float-end">
            <a class="btn btn-adaptive" href="{{ route('admin.template.index') }}"><i class="fas fa-wrench"></i></a>
        </div>
    @endif

    <h1>{{ __('template::template.template') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('template::template.template') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @auth
        <form action="{{ route('template.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">{{ __('template::template.title_field') }}</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="text" class="form-label">{{ __('template::template.text_field') }}</label>
                <textarea name="text" id="text" class="form-control tiptap @error('text') is-invalid @enderror" rows="5" maxlength="1000" required>{{ old('text') }}</textarea>
                <span class="js-textarea-counter"></span>
                @error('text') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ __('template::template.submit') }}</button>
        </form>
    @else
        <div class="alert alert-warning">{{ __('template::template.auth_required') }}</div>
    @endauth

    <hr>

    @forelse($templates as $template)
        <div class="card mb-2">
            <div class="card-body">
                <h5>{{ $template->title }}</h5>
                <div>{{ $template->getText() }}</div>
                <small class="text-muted">{{ $template->user->getName() }} — {{ dateFixed($template->created_at) }}</small>
            </div>
        </div>
    @empty
        <div class="alert alert-info">{{ __('template::template.no_records') }}</div>
    @endforelse

    {{ $templates->links() }}
@endsection
