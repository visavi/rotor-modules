{{--
    Форма удаления живёт отдельно от строки: <form> внутри формы сортировки
    невалидна, браузер её выбрасывает. Кнопка связывается с этой формой
    атрибутом form="board-delete-{id}"
--}}
<form id="board-delete-{{ $board->id }}" action="{{ route('admin.boards.delete', ['id' => $board->id]) }}" method="post" onsubmit="return confirm('{{ __('board::boards.confirm_delete_category') }}')">
    @csrf
    @method('DELETE')
</form>
