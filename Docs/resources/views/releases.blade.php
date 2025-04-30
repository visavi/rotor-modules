@extends('layout')

@section('title', 'Релизы')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item active">Релизы</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($releases)
        <div class="section shadow border p-3 mb-3">
            @foreach ($releases as $release)
                <div class="post mb-3">
                    <div class="post-message fw-bold">
                        <a href="{{ $release['html_url'] }}">{{ $release['name'] }}</a>
                    </div>

                    @if ($release['body'])
                        <div class="post-message">
                            {{ bbCode('[spoiler=Описание]' . $release['body'] . '[/spoiler]') }}
                        </div>
                    @endif

                    <div class="post-author fw-light">
                            <span class="avatar-micro">
                                <img class="avatar-default rounded-circle" src="{{ $release['author']['avatar_url'] }}" alt="Аватар">
                            </span>

                        <span><a href="{{ $release['author']['html_url'] }}">{{ $release['author']['login'] }}</a></span>
                        <small class="post-date text-body-secondary fst-italic">{{ dateFixed(strtotime($release['created_at'])) }}</small>
                    </div>

                    <div>
                        <a href="{{ $release['html_url'] }}">Страница загрузки</a><br>

                        @if (isset($release['assets'][0]))
                            Скачать: <a href="{{ $release['assets'][0]['browser_download_url'] }}">{{ $release['assets'][0]['name'] }}</a> {{ formatSize($release['assets'][0]['size']) }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            Не удалось получить последние версии!
        </div>
    @endif
@stop
