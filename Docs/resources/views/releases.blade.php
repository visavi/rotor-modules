@extends('layout')

@section('title', __('docs::rotor.page_releases'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item active">{{ __('docs::rotor.page_releases') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($releases)
        <div class="rel-feed">
            @foreach ($releases as $i => $release)
                <article class="rel-card{{ $i === 0 ? ' rel-card--latest' : '' }}">
                    <div class="rel-card__aside">
                        <span class="rel-tag">{{ $release['tag_name'] }}</span>
                        @if ($i === 0)
                            <span class="rel-flag rel-flag--latest">{{ __('docs::rotor.latest_badge') }}</span>
                        @endif
                        @if ($release['prerelease'] ?? false)
                            <span class="rel-flag rel-flag--pre">{{ __('docs::rotor.prerelease') }}</span>
                        @endif
                    </div>

                    <div class="rel-card__body">
                        <h2 class="rel-card__title">
                            <a href="{{ $release['html_url'] }}">{{ $release['name'] ?: $release['tag_name'] }}</a>
                        </h2>

                        <div class="rel-meta">
                            <img class="rel-meta__avatar rounded-circle" src="{{ $release['author']['avatar_url'] }}" alt="{{ $release['author']['login'] }}">
                            <a class="rel-meta__author" href="{{ $release['author']['html_url'] }}">{{ $release['author']['login'] }}</a>
                            <span class="rel-meta__sep">&middot;</span>
                            <span class="rel-meta__date">{{ dateFixed(strtotime($release['created_at'])) }}</span>
                        </div>

                        @if ($release['body'])
                            <details class="rel-spoiler spoiler">
                                <summary>{{ __('docs::rotor.description') }}</summary>
                                <div class="rel-spoiler__inner markdown-body">{{ renderMarkdown($release['body']) }}</div>
                            </details>
                        @endif

                        @php
                            $assets = collect($release['assets'] ?? []);
                            $lite = $assets->first(fn ($a) => str_contains($a['name'], 'lite'));
                            $full = $assets->first(fn ($a) => ! str_contains($a['name'], 'lite'));
                            $downloads = (int) $assets->sum('download_count');
                        @endphp

                        <div class="rel-actions">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ $release['html_url'] }}" rel="noopener" target="_blank">
                                <i class="fab fa-github me-1"></i>{{ __('docs::rotor.release_page') }}
                            </a>

                            @if ($full)
                                <a class="btn btn-sm btn-primary" href="{{ $full['browser_download_url'] }}">
                                    <i class="fas fa-download me-1"></i>{{ __('docs::rotor.download_full') }}
                                    <span class="rel-asset__size">{{ formatSize($full['size']) }}</span>
                                </a>
                            @endif

                            @if ($lite)
                                <a class="btn btn-sm btn-outline-primary" href="{{ $lite['browser_download_url'] }}">
                                    <i class="fas fa-feather me-1"></i>{{ __('docs::rotor.download_lite') }}
                                    <span class="rel-asset__size">{{ formatSize($lite['size']) }}</span>
                                </a>
                            @endif

                            @if ($downloads > 0)
                                <span class="rel-asset__count">
                                    <i class="fas fa-arrow-down-long me-1"></i>{{ $downloads }} {{ __('docs::rotor.downloads') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            {{ __('docs::rotor.releases_error') }}
        </div>
    @endif
@stop

@push('styles')
    <style>
        .rel-feed { display: flex; flex-direction: column; gap: 1rem; }

        .rel-card {
            display: flex;
            gap: 1.25rem;
            padding: 1.25rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 1rem;
            background: var(--bs-body-bg);
            transition: border-color .2s, box-shadow .2s;
        }
        .rel-card:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 16px 38px -24px rgba(46, 140, 194, .7);
        }
        .rel-card--latest {
            border-color: var(--bs-primary);
            background:
                linear-gradient(var(--bs-body-bg), var(--bs-body-bg)) padding-box,
                radial-gradient(120% 120% at 0 0, rgba(46, 140, 194, .12), transparent 60%) border-box;
        }

        .rel-card__aside {
            flex-shrink: 0;
            width: 120px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .4rem;
        }
        .rel-tag {
            font-family: var(--bs-font-monospace);
            font-weight: 700;
            font-size: .95rem;
            padding: .25rem .6rem;
            border-radius: .5rem;
            color: var(--bs-primary);
            background: rgba(46, 140, 194, .12);
        }
        .rel-flag {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .15rem .5rem;
            border-radius: 100px;
        }
        .rel-flag--latest { color: #fff; background: var(--bs-primary); }
        .rel-flag--pre { color: var(--bs-warning); border: 1px solid var(--bs-warning); }

        .rel-card__body { flex: 1; min-width: 0; overflow-wrap: anywhere; }
        .rel-card__title { font-size: 1.15rem; font-weight: 700; margin-bottom: .35rem; }
        .rel-card__title a { color: var(--bs-body-color); text-decoration: none; }
        .rel-card__title a:hover { color: var(--bs-primary); }

        .rel-meta {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: var(--bs-secondary-color);
            margin-bottom: .75rem;
        }
        .rel-meta__avatar { width: 22px; height: 22px; }
        .rel-meta__author { color: var(--bs-secondary-color); text-decoration: none; }
        .rel-meta__author:hover { color: var(--bs-primary); }
        .rel-meta__sep { opacity: .5; }

        .rel-spoiler { margin-bottom: .85rem; }
        .rel-spoiler summary { cursor: pointer; color: var(--bs-primary); font-size: .9rem; }
        .rel-spoiler__inner { margin-top: .5rem; font-size: .92rem; color: var(--bs-secondary-color); }
        .markdown-body :is(h1, h2, h3, h4) { font-size: 1rem; font-weight: 700; margin: .85rem 0 .4rem; color: var(--bs-body-color); }
        .markdown-body ul, .markdown-body ol { padding-left: 1.25rem; margin-bottom: .5rem; }
        .markdown-body li { margin-bottom: .2rem; }
        .markdown-body p { margin-bottom: .5rem; }
        .markdown-body code { font-size: .85em; padding: .1rem .35rem; border-radius: .3rem; background: var(--bs-tertiary-bg); }
        .markdown-body pre { padding: .75rem; border-radius: .5rem; background: var(--bs-tertiary-bg); overflow-x: auto; }
        .markdown-body pre code { padding: 0; background: none; }
        .markdown-body a { color: var(--bs-primary); }
        .markdown-body :last-child { margin-bottom: 0; }

        .rel-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .rel-asset__size { opacity: .75; font-size: .8rem; margin-left: .25rem; }
        .rel-asset__count { font-size: .82rem; color: var(--bs-secondary-color); }

        @media (max-width: 575.98px) {
            .rel-feed { gap: .85rem; }
            .rel-card { flex-direction: column; gap: .85rem; padding: 1rem; border-radius: .85rem; }
            .rel-card__aside { flex-direction: row; align-items: center; flex-wrap: wrap; width: auto; gap: .5rem; }
            .rel-card__title { font-size: 1.05rem; }

            .rel-actions { gap: .5rem; }
            /* Кнопки скачивания тянутся в ряд, ссылка на GitHub и счётчик — на всю ширину */
            .rel-actions .btn-primary,
            .rel-actions .btn-outline-primary { flex: 1 1 0; text-align: center; }
            .rel-actions .btn-outline-secondary { flex: 1 1 100%; text-align: center; }
            .rel-asset__count { flex: 1 1 100%; }
        }
    </style>
@endpush
