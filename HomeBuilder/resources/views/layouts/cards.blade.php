{{--
    Вид «Карточки, как в ленте». Карточки рисуют сами модули — их шаблоны из ленты.
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

@use('App\Services\FeedService')

@if ($block->title !== '')
    <div class="section-title mb-2">{{ $block->title }}</div>
@endif

@forelse ((new FeedService())->render($entries->map(static fn ($entry) => $entry->model)) as $card)
    {!! $card !!}
@empty
    <div class="section shadow mb-3 text-muted">{{ __('main.empty_records') }}</div>
@endforelse
