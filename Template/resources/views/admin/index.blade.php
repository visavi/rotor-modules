@extends('layout')

@section('title', __('template::template.admin_title'))

@section('header')
    <div class="float-end">
        <a class="btn btn-adaptive" href="{{ route('template.index') }}"><i class="fas fa-eye"></i></a>
    </div>

    <h1>{{ __('template::template.admin_title') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('template::template.template') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <h2>{{ __('template::template.records') }}</h2>

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
