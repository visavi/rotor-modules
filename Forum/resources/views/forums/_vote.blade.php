@php
    $voted = $vote->isVoted();
    $page = $page ?? 1;
@endphp
{{-- После голоса сервер отдаёт этот же блок целиком, клиент подменяет его по .js-vote --}}
<div class="js-vote">
    <h5>{{ $vote->title }}</h5>

    <div class="mb-3">
        @if ($voted)
            @foreach ($vote->results() as $result)
                <b>{{ $result['answer'] }}</b> ({{ __('forum::forums.votes') }}: {{ $result['result'] }})<br>
                {{ progressBar($result['width'], $result['percent'] . '%') }}
            @endforeach
        @else
            <form class="mb-3" action="{{ route('topics.vote', ['id' => $vote->topic_id]) }}" method="post" data-ajax data-ajax-replace=".js-vote" data-ajax-swap="outer">
                @csrf
                <input type="hidden" name="page" value="{{ $page }}">
                @foreach ($vote->answers as $answer)
                    <label><input name="poll" type="radio" value="{{ $answer->id }}"> {{ $answer->answer }}</label><br>
                @endforeach
                <button class="btn btn-sm btn-primary mt-3">{{ __('forum::forums.vote') }}</button>
            </form>
        @endif

        {{ __('forum::forums.total_votes') }}: {{ $vote->count }}
    </div>
</div>
