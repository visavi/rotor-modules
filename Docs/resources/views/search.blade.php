@extends('layout')

@section('title', 'Поиск по документации')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/docs">Документация</a></li>
            <li class="breadcrumb-item active">Поиск</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="mb-4">
        <form action="/docs/search" method="get" class="d-flex gap-2">
            <input name="q" class="form-control" type="search"
                   placeholder="Поиск по документации..." value="{{ $query }}" minlength="3" maxlength="64">
            <button class="btn btn-primary">Найти</button>
        </form>
    </div>

    @if (mb_strlen($query) > 0 && mb_strlen($query) < 3)
        <div class="alert alert-warning">Минимум 3 символа для поиска.</div>
    @elseif ($query && empty($results))
        <div class="text-muted">Ничего не найдено по запросу «{{ $query }}».</div>
    @elseif (!empty($results))
        <p class="text-muted">Найдено: {{ count($results) }}</p>
        @foreach ($results as $result)
            <div class="card mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ $result['href'] }}" class="fw-semibold text-decoration-none">
                            {{ $result['title'] }}
                        </a>
                        @if ($result['type'] === 'laravel')
                            <span class="badge" style="background:#f05340;font-size:.65rem;">Laravel</span>
                        @else
                            <span class="badge bg-primary" style="font-size:.65rem;">RotorCMS</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
                        {!! nl2br(e($result['excerpt'])) !!}
                    </p>
                </div>
            </div>
        @endforeach
    @endif
@stop
