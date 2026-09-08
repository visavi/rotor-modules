@extends('layout')

@section('title', __('page_editor::files.file_editing') . ' ' . trim($path . '/' . $file, '/'))

@section('breadcrumb')
    @include('page_editor::admin/files/_breadcrumb', ['active' => $file])
@stop

@section('content')
    @if (! $writable)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            {{ __('page_editor::files.writable') }}
        </div>
    @endif

    <div class="section-form mb-3 shadow">
        <form method="post" action="{{ route('admin.files.edit', ['root' => $root, 'path' => $path, 'file' => $file]) }}">
            @csrf
            <div class="mb-3{{ hasError('msg') }}">
                <label for="msg" class="form-label">{{ __('main.text') }}:</label>
                <textarea class="form-control font-monospace" rows="25" id="msg" name="msg" spellcheck="false" data-goto-line="{{ $line }}">{{ old('msg', $contest) }}</textarea>
                <div class="invalid-feedback">{{ textError('msg') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('main.save') }}</button>
        </form>
    </div>

    <p class="text-muted fst-italic">{{ __('page_editor::files.edit_hint') }}</p>

@push('scripts')
    <script>
        (function () {
            const textarea = document.getElementById('msg');
            if (! textarea) {
                return;
            }

            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                    this.selectionStart = this.selectionEnd = start + 4;
                }
            });

            const line = parseInt(textarea.dataset.gotoLine || '0', 10);
            if (! line) {
                return;
            }

            const lines = textarea.value.split('\n');
            let offset = 0;
            for (let i = 0; i < line - 1 && i < lines.length; i++) {
                offset += lines[i].length + 1;
            }

            textarea.focus();
            textarea.setSelectionRange(offset, offset + (lines[line - 1] || '').length);

            const style = window.getComputedStyle(textarea);
            const lineHeight = parseFloat(style.lineHeight) || 18;
            textarea.scrollTop = Math.max(0, (line - 3) * lineHeight);
        })();
    </script>
@endpush
@stop
