@extends('layout')

@section('title', $news->title)

@section('description', truncateDescription($news->text))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('news.index') }}">{{ __('news::news.news') }}</a></li>
            <li class="breadcrumb-item active">{{ $news->title }}</li>
        </ol>
    </nav>
@stop

@section('header')
    @if (isAdmin())
        <div class="float-end">
            <div class="btn-group">
                <button type="button" class="btn btn-adaptive dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-wrench"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('admin.news.edit', ['id' => $news->id]) }}">{{ __('main.edit') }}</a>
                    <form action="{{ route('admin.news.delete', ['id' => $news->id]) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('news::news.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link dropdown-item">{{ __('main.delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <h1>{{ $news->title }}</h1>
@stop

@section('content')
    <div class="section mb-3 shadow">
        <div class="section-content">
            @if ($news->getDetachedMedia()->isNotEmpty())
                @include('app/_media_slider', ['model' => $news, 'files' => $news->getDetachedMedia()])
            @endif

            <div class="section-message">
                {{ $news->getText() }}
            </div>

            @if ($news->getFiles()->isNotEmpty())
                @foreach ($news->getFiles() as $file)
                    <div class="media-file">
                        @if ($file->isVideo())
                            <div>
                                <video src="{{ $file->path }}" class="img-fluid rounded" preload="metadata" controls playsinline></video>
                            </div>
                        @endif

                        @if ($file->isAudio())
                            <div>
                                <audio src="{{ $file->path }}" class="img-fluid rounded" preload="metadata" controls></audio>
                            </div>
                        @endif

                        {{ icons($file->extension) }}
                        <a href="{{ $file->path }}">{{ $file->name }}</a> ({{ formatSize($file->size) }})
                    </div>
                @endforeach
            @endif
        </div>


        <div class="section-body">
            <div class="section-body">
                <span class="avatar-micro">{{ $news->user->getAvatarImage() }}</span> {{ $news->user->getProfile() }} <small class="section-date text-muted fst-italic">{{ dateFixed($news->created_at) }}</small>
            </div>

            <div class="d-flex align-items-center gap-1">
                {{ __('main.rating') }}:
                @include('app/_rating', ['model' => $news, 'vote' => $news->vote])
            </div>
        </div>
    </div>

    <div class="section mb-3 shadow p-0 overflow-hidden" id="comments">
        <div class="px-3 py-2">
            <h5 id="comments"><i class="fa-regular fa-comment"></i> {{ __('main.comments') }}</h5>
        </div>
        <div class="px-3">
            @include('app/_comment_form', [
                'action' => route('news.add-comment', ['id' => $news->id]),
                'closed' => $news->closed,
            ])

            @foreach ($comments as $comment)
                @include('app/_comment_item', ['action' => route('news.add-comment', ['id' => $news->id]), 'closed' => $news->closed, 'ownerId' => $news->user_id])
            @endforeach
        </div>
    </div>
@stop
