@extends('layout')

@section('title', __('forum::forums.title_create'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('forums.index') }}">{{ __('forum::forums.forums') }}</a></li>
            <li class="breadcrumb-item active">{{ __('forum::forums.title_create') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form action="{{ route('forums.create') }}" method="post">
            @csrf
            <div class="mb-3{{ hasError('forum_id') }}">
                <label for="inputForum" class="form-label">{{ __('forum::forums.forum') }}:</label>
                <select class="form-select" id="inputForum" name="forum_id">

                    @foreach ($forums as $data)
                        <option value="{{ $data->id }}"{{ (int) old('forum_id', $fid) === $data->id && ! $data->closed ? ' selected' : '' }}{{ $data->closed ? ' disabled' : '' }}>
                            {{ str_repeat('–', $data->depth) }} {{ $data->title }}
                        </option>
                    @endforeach

                </select>
                <div class="invalid-feedback">{{ textError('forum_id') }}</div>
            </div>

            <div class="mb-3{{ hasError('title') }}">
                <label for="inputTitle" class="form-label">{{ __('forum::forums.topic') }}:</label>
                <input name="title" class="form-control" id="inputTitle" maxlength="{{ setting('forum_title_max') }}" placeholder="{{ __('forum::forums.topic') }}" value="{{ old('title') }}" required>
                <div class="invalid-feedback">{{ textError('title') }}</div>
            </div>

            <div class="mb-3{{ hasError('text') }}">
                <label for="text" class="form-label">{{ __('forum::forums.post') }}:</label>
                <textarea class="form-control tiptap" maxlength="{{ setting('forum_text_max') }}" id="text" rows="5" name="text" data-relate-type="{{ \Modules\Forum\Models\Post::$morphName }}" data-relate-id="0" required>{{ old('text') }}</textarea>
                <div class="invalid-feedback">{{ textError('text') }}</div>
                <span class="js-textarea-counter"></span>
            </div>

            @php
                $checkVote = (bool) old('vote');
            @endphp

            @include('app/_upload_file', [
                'model' => Modules\Forum\Models\Post::getModel(),
                'files' => $files,
            ])

            <label class="form-check mb-3">
                {{-- Снятый флажок браузер не отправляет, без нуля запрос не узнает, что его сняли --}}
                <input type="hidden" name="vote" value="0">
                {{-- autocomplete="off": браузер не восстановит флажок после перезагрузки, иначе он разойдётся с формой голосования --}}
                <input type="checkbox" class="form-check-input" name="vote" value="1" autocomplete="off" data-bs-toggle="collapse" data-bs-target="#vote-form" aria-controls="vote-form" aria-expanded="{{ $checkVote ? 'true' : 'false' }}" @checked($checkVote)> {{ __('forum::forums.create_vote') }}
            </label>

            <div class="collapse{{ $checkVote ? ' show' : '' }}" id="vote-form">
                <div class="mb-3{{ hasError('question') }}">

                    <label for="inputQuestion" class="form-label">{{ __('forum::forums.question') }}:</label>
                    <input type="text" name="question" class="form-control" id="inputQuestion" value="{{ old('question') }}" maxlength="100">
                    <div class="invalid-feedback">{{ textError('question') }}</div>
                </div>

                @include('forum::forums/_vote_answers')
            </div>

            <button class="btn btn-primary">{{ __('forum::forums.create_topic') }}</button>
        </form>
    </div>

    {{ __('forum::forums.create_rule1') }}<br>

    {{ __('forum::forums.create_rule2') }}<br>
    <a href="/search">{{ __('main.search') }}</a><br>
    {{ __('forum::forums.create_rule3') }}<br><br>
@stop
