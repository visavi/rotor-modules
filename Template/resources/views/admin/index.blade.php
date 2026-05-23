@extends('layout')

@section('title', __('template::template.admin_title'))

@section('content')
    <nav class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('template::template.template') }}</li>
        </ol>
    </nav>

    <h1>{{ __('template::template.records') }}</h1>

    @forelse($templates as $template)
        <div class="card mb-2">
            <div class="card-body">
                <h5>{{ $template->title }}</h5>
                <div>{{ $template->getText() }}</div>
                <small class="text-muted">{{ $template->user->getName() }} — {{ dateFixed($template->created_at) }}</small>

                <form action="{{ route('admin.template.delete') }}" method="post" class="mt-2" onsubmit="return confirm('{{ __('template::template.confirm_delete') }}')">
                    @csrf
                    <input type="hidden" name="id" value="{{ $template->id }}">
                    <button type="submit" class="btn btn-sm btn-danger">{{ __('template::template.delete') }}</button>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info">{{ __('template::template.no_records') }}</div>
    @endforelse

    {{ $templates->links() }}
@endsection
