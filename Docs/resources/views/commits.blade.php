@extends('layout')

@section('title', 'Изменения')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item active">Изменения</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($commits)
        <div class="section shadow border p-3 mb-3">
            @foreach ($commits as $commit)
                <div class="post mb-3">
                    <div class="post-message fw-bold">
                        <a href="{{ $commit['html_url'] }}">{{ $commit['commit']['message'] }}</a>
                    </div>

                    <div class="post-author fw-light">
                            <span class="avatar-micro">
                                <img class="avatar-default rounded-circle" src="{{ $commit['author']['avatar_url'] }}" alt="Аватар">
                            </span>

                        <span><a href="{{ $commit['author']['html_url'] }}">{{ $commit['author']['login'] }}</a></span>
                        <small class="post-date text-body-secondary fst-italic">{{ dateFixed(strtotime($commit['commit']['author']['date'])) }}</small>
                    </div>
                </div>
            @endforeach

            {{ $commits->links() }}
        </div>
    @else
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            Не удалось получить последние изменения!
        </div>
    @endif
@stop
