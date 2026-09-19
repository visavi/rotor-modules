<i class="fa fa-folder-open text-muted"></i>

{{-- min-width: 0 — иначе длинный заголовок статьи распирает flex-строку --}}
<span class="flex-grow-1" style="min-width: 0">
    <a href="{{ route('admin.blogs.blog', ['id' => $item->id]) }}" class="fw-bold">{{ $item->name }}</a>

    @if ($item->new)
        <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('blog::blogs.articles') }} / {{ __('main.new') }}">{{ $item->total_articles }}/<span class="text-danger">+{{ $item->new->count_articles }}</span></span>
    @else
        <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('blog::blogs.articles') }} / {{ __('main.new') }}">{{ $item->total_articles }}</span>
    @endif

    <span class="d-block text-muted small text-truncate">
        @if ($item->lastArticle)
            <a href="{{ route('articles.view', ['slug' => $item->lastArticle->slug]) }}" class="text-muted">{{ $item->lastArticle->title }}</a>
            — {{ $item->lastArticle->user->getName() }},
            <span class="fst-italic">{{ dateFixed($item->lastArticle->created_at) }}</span>
        @else
            {{ __('blog::blogs.empty_articles') }}
        @endif
    </span>
</span>

@if (isAdmin('boss'))
    <span class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.blogs.edit', ['id' => $item->id]) }}" title="{{ __('main.edit') }}"><i class="fa fa-pencil-alt"></i></a>

        {{-- Форма удаления лежит ниже, вне формы сортировки: вложенная <form> невалидна
             и браузер её выбрасывает, поэтому кнопка связана с ней атрибутом form --}}
        <button class="btn btn-link p-0 text-danger" form="blog-delete-{{ $item->id }}" title="{{ __('main.delete') }}">
            <i class="fa fa-times"></i>
        </button>
    </span>
@endif
