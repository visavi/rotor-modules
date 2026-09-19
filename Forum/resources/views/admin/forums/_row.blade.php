<i class="fa fa-folder-open text-muted"></i>

<a href="{{ route('admin.forums.forum', ['id' => $item->id]) }}">{{ $item->title }}</a>

<span class="badge bg-adaptive">{{ $item->count_topics }}/{{ $item->count_posts }}</span>

@if ($item->closed)
    <span class="badge bg-danger">{{ __('forum::forums.forum_closed_badge') }}</span>
@endif

@if (isAdmin('boss'))
    <span class="ms-auto d-flex align-items-center gap-2">
        <a href="{{ route('admin.forums.edit', ['id' => $item->id]) }}" title="{{ __('main.edit') }}"><i class="fa fa-pencil-alt"></i></a>

        {{-- Форма удаления лежит ниже, вне формы сортировки: вложенная <form> невалидна
             и браузер её выбрасывает, поэтому кнопка связана с ней атрибутом form --}}
        <button class="btn btn-link p-0 text-danger" form="forum-delete-{{ $item->id }}" title="{{ __('main.delete') }}">
            <i class="fa fa-times"></i>
        </button>
    </span>
@endif
