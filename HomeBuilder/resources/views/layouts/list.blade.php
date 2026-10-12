{{--
    Вид блока «Записи» конструктора главной. Это Blade-шаблон: HTML плюс вставки {{ }} и @директивы.

    $block   — блок: $block->title — заголовок (может быть пустым)
    $entries — записи выбранных разделов, свежие первыми. У каждой $entry:
        title      — заголовок
        url        — ссылка на запись
        date       — дата, вывод: dateFixed($entry->date)
        text       — анонс без разметки
        image      — первая картинка или null
        author     — автор или null: $entry->author?->getProfile(), $entry->author?->getAvatarImage()
        section    — название раздела, sectionUrl — ссылка на раздел
        comments   — число комментариев, rating — рейтинг
        model      — исходная запись раздела, если нужно что-то своё

    Свои стили — @push('styles') ... @endpush, скрипты — @push('scripts') ... @endpush
--}}

<div class="section shadow mb-3">
    @if ($block->title !== '')
        <div class="section-title">{{ $block->title }}</div>
    @endif

    <div class="section-body">
        @forelse ($entries as $entry)
            <i class="far fa-circle text-muted"></i> <a href="{{ $entry->url }}">{{ $entry->title }}</a>
            @if ($entry->comments)
                <span class="badge bg-adaptive">{{ $entry->comments }}</span>
            @endif
            <br>
        @empty
            <span class="text-muted">{{ __('main.empty_records') }}</span>
        @endforelse
    </div>
</div>
