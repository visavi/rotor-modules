@extends('layout')

@section('title', __('caches::caches.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('caches::caches.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')

    <div class="mb-3">
        <?php $active = ($type === 'files') ? 'primary' : 'adaptive'; ?>
        <a class="btn btn-{{ $active }} btn-sm" href="{{ route('admin.caches.index', ['type' => 'files']) }}">{{ __('caches::caches.files') }}</a>

        <?php $active = ($type === 'views') ? 'primary' : 'adaptive'; ?>
        <a class="btn btn-{{ $active }} btn-sm" href="{{ route('admin.caches.index', ['type' => 'views']) }}">{{ __('caches::caches.views') }}</a>
    </div>

    <div class="mb-3">
        <span class="badge bg-success">App env: {{ config('app.env') }}</span>
        <span class="badge bg-success">Cache driver: {{ config('cache.default') }}</span>
    </div>
    <hr>

    @if ($files->isNotEmpty())
        <div class="mb-3">
            @foreach ($files as $file)
                <div class="mb-1">
                    <i class="fa fa-file-alt"></i> <b>{{ basename($file) }}</b> ({{ formatFileSize($file) }} / {{ dateFixed(\Illuminate\Support\Facades\Date::createFromTimestamp(filemtime($file))) }})
                </div>
            @endforeach
        </div>

        {{ $files->links() }}

        <div class="mb-3">
            {{ __('main.total') }}: {{ $files->total() }}
        </div>
    @elseif ($type === 'files' && config('cache.default') !== 'file')
        <div class="alert alert-info">
            {{ __('caches::caches.only_file_cache') }}
        </div>
    @else
        {{ showError(__('caches::caches.empty_files')) }}
    @endif

    <div class="float-end">
        <form action="{{ route('admin.caches.clear', ['type' => $type]) }}" method="post">
            @csrf
            <button class="btn btn-sm btn-danger"><i class="fa fa-trash-alt"></i> {{ __('caches::caches.clear') }}</button>
        </form>
    </div>
@stop
