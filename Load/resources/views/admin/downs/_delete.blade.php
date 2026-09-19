{{--
    Форма удаления живёт отдельно от строки: <form> внутри формы сортировки
    невалидна, браузер её выбрасывает. Кнопка связывается с этой формой
    атрибутом form="load-delete-{id}"
--}}
<form id="load-delete-{{ $load->id }}" action="{{ route('admin.loads.delete', ['id' => $load->id]) }}" method="post" onsubmit="return confirm('{{ __('load::loads.confirm_delete_load') }}')">
    @csrf
    @method('DELETE')
</form>
