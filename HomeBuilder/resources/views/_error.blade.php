@use('Illuminate\Support\Str')

{{-- Ошибка в шаблоне блока: её видит только владелец, остальным блок не показывается --}}
<div class="alert alert-danger mb-3">
    <div class="fw-bold">{{ __('home_builder::home_builder.render_error', ['title' => $block->title !== '' ? $block->title : $block->getTypeLabel(), 'template' => $block->template]) }}</div>
    <div class="small text-break">{{ Str::limit(preg_replace('/ \(View: .*\)$/', '', $error), 300) }}</div>

    <a href="{{ route('admin.home-builder.edit', ['id' => $block->id]) }}" class="small">{{ __('home_builder::home_builder.edit_block') }}</a>
</div>
