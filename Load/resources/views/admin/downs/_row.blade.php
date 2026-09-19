<i class="fa fa-folder-open text-muted"></i>

{{-- min-width: 0 — иначе длинное название файла распирает flex-строку --}}
<span class="flex-grow-1" style="min-width: 0">
    <a href="{{ route('admin.loads.load', ['id' => $item->id]) }}" class="fw-bold">{{ $item->name }}</a>

    @if ($item->new)
        <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('load::loads.downs') }} / {{ __('main.new') }}">{{ $item->total_downs }}/<span class="text-danger">+{{ $item->new->count_downs }}</span></span>
    @else
        <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('load::loads.downs') }} / {{ __('main.new') }}">{{ $item->total_downs }}</span>
    @endif

    @if ($item->closed)
        <span class="badge bg-danger">{{ __('load::loads.closed_load') }}</span>
    @endif

    <span class="d-block text-muted small text-truncate">
        @if ($item->lastDown)
            <a href="{{ route('downs.view', ['id' => $item->lastDown->id]) }}" class="text-muted">{{ $item->lastDown->title }}</a>
            — {{ $item->lastDown->user->getName() }},
            <span class="fst-italic">{{ dateFixed($item->lastDown->created_at) }}</span>
        @else
            {{ __('load::loads.empty_downs') }}
        @endif
    </span>
</span>

@if (isAdmin('boss'))
    <span class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.loads.edit', ['id' => $item->id]) }}" title="{{ __('main.edit') }}"><i class="fa fa-pencil-alt"></i></a>

        {{-- Форма удаления лежит ниже, вне формы сортировки: вложенная <form> невалидна
             и браузер её выбрасывает, поэтому кнопка связана с ней атрибутом form --}}
        <button class="btn btn-link p-0 text-danger" form="load-delete-{{ $item->id }}" title="{{ __('main.delete') }}">
            <i class="fa fa-times"></i>
        </button>
    </span>
@endif
