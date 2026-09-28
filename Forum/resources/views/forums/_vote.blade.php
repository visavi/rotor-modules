@php
    $voted = $vote->isVoted();
    $page = $page ?? 1;
@endphp
{{-- После голоса сервер отдаёт этот же блок целиком, клиент подменяет его по .js-vote-<id>:
     класс уникален для голосования, иначе ответ подменил бы все опросы ленты разом --}}
<div class="js-vote-{{ $vote->id }}">
    <h5>{{ $vote->title }}</h5>

    <div class="mb-3">
        @unless ($voted)
            <form class="mb-3" action="{{ route('topics.vote', ['id' => $vote->topic_id]) }}" method="post" data-ajax data-ajax-replace=".js-vote-{{ $vote->id }}" data-ajax-swap="outer">
                @csrf
                <input type="hidden" name="page" value="{{ $page }}">
                @foreach ($vote->answers as $answer)
                    <label><input name="poll" type="radio" value="{{ $answer->id }}"> {{ $answer->answer }}</label><br>
                @endforeach
                <div class="d-flex align-items-center gap-3 mt-3">
                    <button class="btn btn-sm btn-primary">{{ __('forum::forums.vote') }}</button>
                    <a href="#vote-results-{{ $vote->id }}" data-bs-toggle="collapse">{{ __('forum::forums.results') }}</a>
                </div>
            </form>
        @endunless

        {{-- До голоса результаты свернуты, раскрываются ссылкой из формы --}}
        <div @class(['collapse mb-3' => ! $voted]) id="vote-results-{{ $vote->id }}">
            @foreach ($vote->results() as $result)
                <b>{{ $result['answer'] }}</b> ({{ __('forum::forums.votes') }}: {{ $result['result'] }})<br>
                {{ progressBar($result['width'], $result['percent'] . '%') }}
            @endforeach
        </div>

        {{ __('forum::forums.total_votes') }}: {{ $vote->count }}
    </div>
</div>
