<i class="fa fa-folder-open text-muted"></i>

<span class="flex-grow-1" style="min-width: 0">
    <a href="{{ route('admin.boards.index', ['id' => $item->id]) }}" class="fw-bold">{{ $item->name }}</a>

    <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('board::boards.boards') }}">{{ $item->total_items }}</span>
</span>

<span class="d-flex align-items-center gap-2">
    <a href="{{ route('admin.boards.edit', ['id' => $item->id]) }}" title="{{ __('main.edit') }}"><i class="fa fa-pencil-alt"></i></a>

    {{-- Форма удаления лежит ниже, вне формы сортировки: вложенная <form> невалидна
         и браузер её выбрасывает, поэтому кнопка связана с ней атрибутом form --}}
    <button class="btn btn-link p-0 text-danger" form="board-delete-{{ $item->id }}" title="{{ __('main.delete') }}">
        <i class="fa fa-times"></i>
    </button>
</span>
