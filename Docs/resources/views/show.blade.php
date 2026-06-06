@extends('layout')

@section('title', $title ?? 'Документация')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/docs">Документация</a></li>
            <li class="breadcrumb-item active">{{ $title ?? $page }}</li>
        </ol>
    </nav>
@stop

@php
    $hasSidebarSlot = view()->exists('theme::sidebar');
@endphp

@include('docs::_sidebar')

@section('content')
    @php
        $docsBody = !($synced ?? true)
            ? '<div class="alert alert-warning"><strong>Документация не загружена.</strong> Запустите команду: <code>php artisan docs:sync</code></div>'
            : $content;
    @endphp

    @if ($hasSidebarSlot)
        <article class="docs-content">
            {!! $docsBody !!}
        </article>
    @else
        <div class="docs-layout">
            <nav class="docs-sidebar">
                @include('docs::_nav')
            </nav>

            <article class="docs-content">
                {!! $docsBody !!}
            </article>
        </div>
    @endif
@stop

@push('styles')
    <style>
        /* Двухколоночный layout (темы без sidebar): навигация + контент */
        .docs-layout { display: flex; gap: 1.5rem; align-items: flex-start; }
        .docs-layout .docs-sidebar { width: 260px; flex-shrink: 0; position: sticky; top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto; }
        .docs-content { flex: 1; min-width: 0; }

        /* Стили контента */
        .docs-content h1:first-child { display: none; }
        .docs-content h1:first-child + p:empty { display: none; }
        .docs-content h2 { font-size: 1.3rem; font-weight: 600; margin-top: 2rem; margin-bottom: .75rem; border-bottom: 1px solid var(--bs-border-color); padding-bottom: .5rem; }
        .docs-content h2:first-of-type { margin-top: 0; }
        .docs-content h3 { font-size: 1.1rem; font-weight: 600; margin-top: 1.5rem; }
        .docs-content table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .docs-content table th, .docs-content table td { border: 1px solid var(--bs-border-color); padding: .5rem .75rem; font-size: .875rem; }
        .docs-content table th { background: var(--bs-tertiary-bg); font-weight: 600; }
    </style>
@endpush

@push('scripts')
    <script>
        document.querySelectorAll('.docs-content pre').forEach(function (pre) {
            pre.classList.add('code');
        });
    </script>
@endpush
