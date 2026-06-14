@extends('layout')

@section('title', __('docs::rotor.page_commits'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item active">{{ __('docs::rotor.page_commits') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($commits->isNotEmpty())
        <div class="cmt-timeline">
            @foreach ($commits as $commit)
                @php($message = strtok($commit['commit']['message'], "\n"))
                <div class="cmt-item">
                    <a class="cmt-item__avatar" href="{{ $commit['author']['html_url'] ?? '#' }}">
                        <img class="rounded-circle" src="{{ $commit['author']['avatar_url'] ?? '' }}" alt="{{ $commit['author']['login'] ?? '' }}">
                    </a>

                    <div class="cmt-item__body">
                        <a class="cmt-item__msg" href="{{ $commit['html_url'] }}" rel="noopener" target="_blank">
                            {{ mb_strimwidth($message, 0, 90, '…') }}
                        </a>

                        <div class="cmt-item__meta">
                            <a class="cmt-item__author" href="{{ $commit['author']['html_url'] ?? '#' }}">{{ $commit['author']['login'] ?? $commit['commit']['author']['name'] }}</a>
                            <span class="cmt-item__sep">&middot;</span>
                            <span class="cmt-item__date">{{ dateFixed(strtotime($commit['commit']['author']['date'])) }}</span>
                            <a class="cmt-item__sha" href="{{ $commit['html_url'] }}" rel="noopener" target="_blank">
                                <i class="fas fa-code-commit me-1"></i>{{ substr($commit['sha'], 0, 7) }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $commits->links() }}
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            {{ __('docs::rotor.commits_error') }}
        </div>
    @endif
@stop

@push('styles')
    <style>
        .cmt-timeline {
            position: relative;
            margin: 0 0 1.5rem;
            padding-left: 1.75rem;
        }
        .cmt-timeline::before {
            content: '';
            position: absolute;
            top: .5rem;
            bottom: .5rem;
            left: 18px;
            width: 2px;
            background: var(--bs-border-color);
        }

        .cmt-item {
            position: relative;
            display: flex;
            gap: .85rem;
            padding: .65rem 0;
        }
        .cmt-item__avatar {
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            margin-left: -1.75rem;
        }
        .cmt-item__avatar img {
            width: 38px; height: 38px;
            border: 2px solid var(--bs-body-bg);
            box-shadow: 0 0 0 1px var(--bs-border-color);
        }

        .cmt-item__body {
            flex: 1;
            min-width: 0;
            padding-bottom: .65rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .cmt-item:last-child .cmt-item__body { border-bottom: 0; }

        .cmt-item__msg {
            display: block;
            font-weight: 600;
            color: var(--bs-body-color);
            text-decoration: none;
            line-height: 1.4;
        }
        .cmt-item__msg:hover { color: var(--bs-primary); }

        .cmt-item__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
            margin-top: .25rem;
            font-size: .82rem;
            color: var(--bs-secondary-color);
        }
        .cmt-item__author { color: var(--bs-secondary-color); text-decoration: none; }
        .cmt-item__author:hover { color: var(--bs-primary); }
        .cmt-item__sep { opacity: .5; }
        .cmt-item__sha {
            margin-left: auto;
            font-family: var(--bs-font-monospace);
            font-size: .78rem;
            color: var(--bs-primary);
            text-decoration: none;
            padding: .1rem .45rem;
            border-radius: .4rem;
            background: rgba(46, 140, 194, .1);
        }
        .cmt-item__sha:hover { background: rgba(46, 140, 194, .2); }
    </style>
@endpush
