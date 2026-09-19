{{--
    Форма удаления живёт отдельно от строки: <form> внутри формы сортировки
    невалидна, браузер её выбрасывает. Кнопка связывается с этой формой
    атрибутом form="forum-delete-{id}"
--}}
<form id="forum-delete-{{ $forum->id }}" action="{{ route('admin.forums.delete', ['id' => $forum->id]) }}" method="post" onsubmit="return confirm('{{ __('forum::forums.confirm_delete_forum') }}')">
    @csrf
    @method('DELETE')
</form>
