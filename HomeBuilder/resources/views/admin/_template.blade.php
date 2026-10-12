@use('Modules\HomeBuilder\Models\Block')

@php($templates = Block::templates())
@php($inputTemplate = old('template', $block->template))

<div class="mb-3{{ hasError('template') }}">
    <label for="template" class="form-label">{{ __('home_builder::home_builder.template') }}:</label>
    @if ($templates)
        <select class="form-select" id="template" name="template">
            @foreach ($templates as $template)
                <option value="{{ $template }}"{{ $inputTemplate === $template ? ' selected' : '' }}>{{ $template }}</option>
            @endforeach
        </select>
    @else
        <div class="text-muted">{{ __('home_builder::home_builder.template_empty') }}</div>
    @endif
    <div class="invalid-feedback">{{ textError('template') }}</div>
    <div class="form-text">
        {{ $hint ?? __('home_builder::home_builder.template_hint') }}

        @if (Route::has('admin.files.edit') && in_array($inputTemplate, $templates, true))
            — <a href="{{ route('admin.files.edit', ['root' => 'custom', 'path' => 'views/' . Block::TEMPLATE_DIR, 'file' => $inputTemplate . '.blade.php']) }}" target="_blank">{{ __('home_builder::home_builder.template_edit') }}</a>
        @endif
    </div>
</div>

<div class="mb-3{{ hasError('template_name') }}">
    <label for="template_name" class="form-label">{{ __('home_builder::home_builder.template_new') }}:</label>
    <div class="input-group">
        <input type="text" class="form-control" id="template_name" name="template_name" maxlength="50" value="{{ old('template_name') }}" placeholder="promo">
        <span class="input-group-text">.blade.php</span>
    </div>
    <div class="invalid-feedback d-block">{{ textError('template_name') }}</div>
    <div class="form-text">{{ __('home_builder::home_builder.template_new_hint') }}</div>
</div>
