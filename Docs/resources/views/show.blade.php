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

@section('content')
    <div class="docs-layout">
        <nav class="docs-sidebar">
            @include('docs::_nav')
        </nav>

        <article class="docs-content">
            @if (!($synced ?? true))
                <div class="alert alert-warning">
                    <strong>Документация не загружена.</strong>
                    Запустите команду: <code>php artisan docs:sync</code>
                </div>
            @else
                {!! $content !!}
            @endif
        </article>
    </div>
@stop

@push('styles')
    <style>
        /* Двухколоночный layout: навигация + контент */
        .docs-layout { display: flex; gap: 1.5rem; align-items: flex-start; }
        .docs-sidebar { width: 260px; flex-shrink: 0; position: sticky; top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto; }
        .docs-content { flex: 1; min-width: 0; }

        /* Стили навигации */
        .docs-nav-group { margin-bottom: .5rem; }
        .docs-nav-group-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--bs-secondary-color); padding: .25rem .5rem; margin-bottom: .25rem; }
        .docs-nav-section-toggle { display: flex; justify-content: space-between; align-items: center; width: 100%; font-size: .8rem; font-weight: 600; padding: .3rem .5rem; margin-top: .25rem; color: var(--bs-body-color); background: none; border: none; border-radius: .375rem; text-align: left; cursor: pointer; }
        .docs-nav-section-toggle:hover { background: var(--bs-tertiary-bg); }
        .docs-nav-section-toggle::after { content: ''; display: inline-block; width: .4rem; height: .4rem; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor; transform: rotate(225deg); transition: transform .2s; flex-shrink: 0; margin-left: .4rem; opacity: .5; }
        .docs-nav-section-toggle.collapsed::after { transform: rotate(45deg); }
        .docs-nav-item { display: block; padding: .3rem .5rem; border-radius: .375rem; font-size: .875rem; color: var(--bs-body-color); text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .docs-nav-item:hover { background: var(--bs-tertiary-bg); color: var(--bs-body-color); }
        .docs-nav-item.active { background: var(--bs-primary); color: #fff; }
        .docs-divider { border-top: 1px solid var(--bs-border-color); margin: 1rem 0; }
        .docs-badge { font-size: .65rem; font-weight: 600; background: #f05340; color: #fff; border-radius: .25rem; padding: .1rem .35rem; vertical-align: middle; margin-left: .3rem; }

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

        var acc = document.getElementById('docs-laravel-accordion');
        if (acc) {
            acc.addEventListener('show.bs.collapse', function (e) {
                acc.querySelectorAll('.collapse.show').forEach(function (el) {
                    if (el !== e.target) {
                        var btn = acc.querySelector('[data-bs-target="#' + el.id + '"]');
                        if (btn) btn.click();
                    }
                });
            });
        }
    </script>
@endpush
