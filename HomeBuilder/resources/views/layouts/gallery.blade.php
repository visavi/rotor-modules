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

@php($withImages = $entries->filter(static fn ($entry) => $entry->image))

<div class="section shadow mb-3">
    @if ($block->title !== '')
        <div class="section-title">{{ $block->title }}</div>
    @endif

    {{-- Записи без картинок в плитку не попадают --}}
    @if ($withImages->isEmpty())
        <span class="text-muted">{{ __('home_builder::home_builder.gallery_empty') }}</span>
    @else
        <div class="row row-cols-3 row-cols-md-4 g-2">
            @foreach ($withImages as $entry)
                <div class="col">
                    <a href="{{ $entry->url }}" class="ratio ratio-1x1 d-block" title="{{ $entry->title }}">
                        <img src="{{ $entry->image }}" alt="{{ $entry->title }}" class="rounded object-fit-cover" loading="lazy">
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
