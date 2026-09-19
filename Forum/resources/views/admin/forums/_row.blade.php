<i class="fa fa-folder-open text-muted"></i>

{{-- min-width: 0 — иначе длинная тема в подстроке распирает flex-строку --}}
<span class="flex-grow-1" style="min-width: 0">
    <a href="{{ route('admin.forums.forum', ['id' => $item->id]) }}" class="fw-bold">{{ $item->title }}</a>

    <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('forum::forums.topics') }} / {{ __('forum::forums.posts') }}">{{ $item->total_topics }}/{{ $item->total_posts }}</span>

    @if ($item->closed)
        <span class="badge bg-danger">{{ __('forum::forums.forum_closed_badge') }}</span>
    @endif

    <span class="d-block text-muted small text-truncate">
        @if ($item->lastTopic->lastPost->id)
            <a href="{{ route('topics.topic', ['id' => $item->lastTopic->id]) }}" class="text-muted">{{ $item->lastTopic->title }}</a>
            — {{ $item->lastTopic->lastPost->user->getName() }},
            <span class="fst-italic">{{ dateFixed($item->lastTopic->lastPost->created_at) }}</span>
        @else
            {{ __('forum::forums.empty_posts') }}
        @endif
    </span>
</span>

@if (isAdmin('boss'))
    <span class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.forums.edit', ['id' => $item->id]) }}" title="{{ __('main.edit') }}"><i class="fa fa-pencil-alt"></i></a>

        {{-- Форма удаления лежит ниже, вне формы сортировки: вложенная <form> невалидна
             и браузер её выбрасывает, поэтому кнопка связана с ней атрибутом form --}}
        <button class="btn btn-link p-0 text-danger" form="forum-delete-{{ $item->id }}" title="{{ __('main.delete') }}">
            <i class="fa fa-times"></i>
        </button>
    </span>
@endif
