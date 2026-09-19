{{--
    Форма удаления живёт отдельно от строки: <form> внутри формы сортировки
    невалидна, браузер её выбрасывает. Кнопка связывается с этой формой
    атрибутом form="blog-delete-{id}"
--}}
<form id="blog-delete-{{ $blog->id }}" action="{{ route('admin.blogs.delete', ['id' => $blog->id]) }}" method="post" onsubmit="return confirm('{{ __('blog::blogs.confirm_delete_blog') }}')">
    @csrf
    @method('DELETE')
</form>
