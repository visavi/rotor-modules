@use('Modules\HomeBuilder\Models\Block')

@if ($blocks->isEmpty())
    {{ showError(__('home_builder::home_builder.empty')) }}
@endif

<div class="row">
    @foreach ($blocks as $block)
        @php($view = match ($block->type) {
            Block::ENTRIES  => $block->layoutView(),
            Block::TEMPLATE => $block->templateView(),
            default         => null,
        })

        {{-- Файл своего шаблона удалён --}}
        @continue(in_array($block->type, [Block::ENTRIES, Block::TEMPLATE], true) && ! $view)

        <div class="{{ $block->columnClass() }}">
            @switch ($block->type)
                @case (Block::TEXT)
                    <div class="section shadow mb-3">
                        @if ($block->title !== '')
                            <div class="section-title">{{ $block->title }}</div>
                        @endif

                        <div class="section-message">
                            {{ renderHtml($block->text, 'home-block-' . $block->id) }}
                        </div>
                    </div>
                    @break

                {{-- HTML-код и шаблон рисуют блок целиком: рамка и заголовок — в их разметке --}}
                @case (Block::HTML)
                    {!! $block->code !!}
                    @break

                @case (Block::TEMPLATE)
                    {{ $block->renderView($view, ['block' => $block]) }}
                    @break

                @case (Block::ENTRIES)
                    {{ $block->renderView($view, ['block' => $block, 'entries' => $block->entries()]) }}
                    @break
            @endswitch
        </div>
    @endforeach
</div>
