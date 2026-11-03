@extends('layout')

@section('title', __('privacy::privacy.admin_title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('privacy::privacy.admin_title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="alert alert-info">{{ __('privacy::privacy.editor_hint') }}</div>

    <div class="section-form mb-3 shadow">
        <form action="{{ route('privacy.admin.index') }}" method="post">
            @csrf
            <div class="mb-3{{ hasError('text') }}">
                <label for="text" class="form-label">
                    {{ __('privacy::privacy.version', ['id' => $current->id, 'date' => dateFixed($current->created_at, 'd.m.Y', true)]) }}:
                </label>
                {{-- Текст как сохранён: фильтры вывода не должны попасть в следующую версию --}}
                <textarea class="form-control tiptap" id="text" rows="25" name="text" required>{{ old('text', $current->rawText()) }}</textarea>
                <div class="invalid-feedback">{{ textError('text') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('privacy::privacy.publish') }}</button>
            <a class="btn btn-link" href="{{ route('privacy.index') }}" target="_blank">{{ __('privacy::privacy.view_page') }}</a>
        </form>
    </div>

    <h3>{{ __('privacy::privacy.versions') }}</h3>

    <div class="section mb-3 shadow">
        @foreach ($versions as $version)
            <div class="section-body border-bottom">
                <a href="{{ route('privacy.admin.show', ['id' => $version->id]) }}">
                    {{ __('privacy::privacy.version', ['id' => $version->id, 'date' => dateFixed($version->created_at, 'd.m.Y / H:i', true)]) }}
                </a>
                @if ($version->id === $current->id)
                    <span class="badge bg-success">{{ __('privacy::privacy.current') }}</span>
                @endif
                <div class="small text-muted">{{ __('privacy::privacy.consents_count') }}: {{ $version->consents_count }}</div>
            </div>
        @endforeach
    </div>

    {{ $versions->links() }}
@stop
