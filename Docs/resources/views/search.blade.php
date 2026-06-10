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

@include('docs::_sidebar')

@section('content')
    <div class="mb-4">
        <form action="/docs/search" method="get" class="d-flex gap-2">
            <input name="query" class="form-control" type="search"
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
                            <span class="badge bg-danger">Laravel</span>
                        @else
                            <span class="badge bg-primary">RotorCMS</span>
                        @endif
                    </div>
                    <div class="docs-excerpt text-muted mt-1" style="font-size:.85rem;">
                        {!! \Illuminate\Support\Str::of($result['excerpt'])->markdown(['html_input' => 'strip']) !!}
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@stop

@push('scripts')
    <script type="module">
        const query = new URLSearchParams(window.location.search).get('query');

        if (query) {
            const searchWords = query.split(' ')
                .filter(word => word.length >= 3)
                .filter((word, index, self) => self.indexOf(word) === index);

            if (searchWords.length > 0) {
                const regex = new RegExp('(' + searchWords.join('|') + ')', 'gi');

                document.querySelectorAll('.docs-excerpt').forEach(function (el) {
                    el.innerHTML = el.innerHTML.replace(
                        /(<[^>]+>)|([^<]+)/g,
                        (m, tag, text) => tag || text.replace(regex, '<mark>$1</mark>')
                    );
                });
            }
        }
    </script>
@endpush
