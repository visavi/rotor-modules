@extends('layout')

@section('title', __('page_editor::files.file_editing') . ' ' . trim($path . '/' . $file, '/'))

@section('breadcrumb')
    @include('page_editor::admin/files/_breadcrumb', ['active' => $file])
@stop

@section('content')
    @if ($readOnly)
        <div class="alert alert-secondary">
            <i class="fas fa-eye"></i> {{ __('page_editor::files.read_only') }}
        </div>
    @endif

    @if ($root === 'modules')
        <div class="alert alert-warning">
            <i class="fas fa-box-open"></i>
            {{ __('page_editor::files.module_file_warning') }}
            @if ($override)
                {{ __('page_editor::files.module_file_hint') }}
            @endif
        </div>
    @endif

    @if ($original && ! $original['exists'])
        {{-- Правка без оригинала: файл переименовали или удалили, подменять нечего --}}
        <div class="alert alert-warning">
            <i class="fas fa-unlink"></i>
            {{ __('page_editor::files.override_orphan') }}
        </div>
    @elseif ($original)
        <div class="alert alert-info">
            <i class="fas fa-code-branch"></i>
            {{ __('page_editor::files.override_of') }}
            <a href="{{ route('admin.files.edit', ['root' => $original['root'], 'path' => $original['path'], 'file' => $original['file']]) }}">{{ implode('/', array_filter([$original['root'], $original['path'], $original['file']])) }}</a>
        </div>
    @elseif ($override && $override['exists'])
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            {{ __('page_editor::files.override_link') }}
            <a href="{{ route('admin.files.edit', ['root' => $override['root'], 'path' => $override['path'], 'file' => $override['file']]) }}">{{ implode('/', array_filter([$override['root'], $override['path'], $override['file']])) }}</a>
        </div>
    @endif

    @if (! $writable)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            {{ __('page_editor::files.writable') }}
        </div>
    @endif

    <div class="section-form mb-3 shadow">
        <form method="post" action="{{ route('admin.files.edit', ['root' => $root, 'path' => $path, 'file' => $file]) }}">
            @csrf
            {{-- Панель редактора: отмена и поиск по открытому файлу --}}
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 js-editor-toolbar" hidden>
                @unless ($readOnly)
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary js-editor-undo" type="button" title="{{ __('page_editor::files.undo') }}" disabled>
                            <i class="fas fa-rotate-left"></i>
                        </button>
                        <button class="btn btn-outline-primary js-editor-redo" type="button" title="{{ __('page_editor::files.redo') }}" disabled>
                            <i class="fas fa-rotate-right"></i>
                        </button>
                    </div>
                @endunless

                <div class="input-group input-group-sm" style="max-width: 320px">
                    <input class="form-control js-editor-search" type="search" placeholder="{{ __('page_editor::files.find_in_file') }}">
                    <button class="btn btn-outline-secondary js-editor-prev" type="button" title="{{ __('page_editor::files.find_prev') }}">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button class="btn btn-outline-secondary js-editor-next" type="button" title="{{ __('page_editor::files.find_next') }}">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>

                <span class="small text-muted js-editor-counter"></span>
            </div>

            <div class="mb-3{{ hasError('msg') }}">
                <textarea class="form-control font-monospace" rows="25" id="msg" name="msg" spellcheck="false"
                          data-goto-line="{{ $line }}" data-language="{{ $language }}" @readonly($readOnly)>{{ old('msg', $contest) }}</textarea>
                <div class="invalid-feedback">{{ textError('msg') }}</div>
            </div>

            @unless ($readOnly)
            <button class="btn btn-primary" name="target" value="original">{{ __('main.save') }}</button>
            @endunless

            @if ($override && ! $readOnly)
                {{-- Оригинал остаётся чистым: копия уходит в custom и переживёт обновление --}}
                <button class="btn btn-outline-primary" name="target" value="custom">{{ __('page_editor::files.save_to_custom') }}</button>
            @endif
        </form>
    </div>

    @unless ($readOnly)
    <p class="text-muted fst-italic">{{ __('page_editor::files.edit_hint') }}</p>
    @endunless

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/modules/pageeditors/editor/prism.min.css') }}">

    <style>
        .js-code-editor {
            min-height: 480px;
            max-height: 70vh;
            overflow: auto;
            resize: vertical;
            tab-size: 4;
            white-space: pre;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        /* Тема Prism светлая: в тёмном оформлении правим только фон и базовый цвет */
        [data-bs-theme="dark"] .js-code-editor {
            background: var(--bs-tertiary-bg);
        }

        [data-bs-theme="dark"] .js-code-editor .token.comment,
        [data-bs-theme="dark"] .js-code-editor .token.prolog,
        [data-bs-theme="dark"] .js-code-editor .token.doctype {
            color: #6a9955;
        }

        [data-bs-theme="dark"] .js-code-editor .token.string,
        [data-bs-theme="dark"] .js-code-editor .token.attr-value {
            color: #ce9178;
        }

        [data-bs-theme="dark"] .js-code-editor .token.keyword,
        [data-bs-theme="dark"] .js-code-editor .token.tag {
            color: #569cd6;
        }

        [data-bs-theme="dark"] .js-code-editor .token.punctuation,
        [data-bs-theme="dark"] .js-code-editor .token.operator {
            color: var(--bs-body-color);
        }

        [data-bs-theme="dark"] .js-code-editor .token.property,
        [data-bs-theme="dark"] .js-code-editor .token.symbol,
        [data-bs-theme="dark"] .js-code-editor .token.constant,
        [data-bs-theme="dark"] .js-code-editor .token.number,
        [data-bs-theme="dark"] .js-code-editor .token.boolean {
            color: #b5cea8;
        }

        [data-bs-theme="dark"] .js-code-editor .token.variable,
        [data-bs-theme="dark"] .js-code-editor .token.attr-name {
            color: #9cdcfe;
        }

        [data-bs-theme="dark"] .js-code-editor .token.function,
        [data-bs-theme="dark"] .js-code-editor .token.class-name {
            color: #dcdcaa;
        }

        /* Bootstrap глушит disabled лишь до 0.65 — активную кнопку видно плохо */
        .js-editor-toolbar .btn:disabled {
            opacity: .3;
            border-color: var(--bs-border-color);
            color: var(--bs-secondary-color);
        }

        /* Тема рисует операторам полупрозрачный белый фон — на тёмном это серые пятна */
        .js-code-editor .token.operator,
        .js-code-editor .token.entity,
        .js-code-editor .token.url,
        .js-code-editor .language-css .token.string,
        .js-code-editor .style .token.string {
            background: none;
        }
    </style>
@endpush

@push('scripts')
    {{-- Ядро Prism вместе с грамматиками markup-templating и php одним файлом --}}
    <script src="{{ asset('assets/modules/pageeditors/editor/prism.min.js') }}" data-manual></script>

    <script type="module">
        import { CodeJar } from '{{ asset('assets/modules/pageeditors/editor/codejar.js') }}';

        const textarea = document.getElementById('msg');
        const language = textarea?.dataset.language || 'none';
        const line = parseInt(textarea?.dataset.gotoLine || '0', 10);

        // Смещение в тексте указывает внутрь одного из текстовых узлов подсветки
        const pointAt = (element, offset) => {
            const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
            let passed = 0;

            while (walker.nextNode()) {
                const node = walker.currentNode;
                const length = node.textContent.length;

                if (passed + length >= offset) {
                    return [node, offset - passed];
                }

                passed += length;
            }

            return null;
        };

        const selectRange = (element, from, to) => {
            const start = pointAt(element, from);
            const end = pointAt(element, to);

            if (! start || ! end) {
                return false;
            }

            const range = document.createRange();
            range.setStart(start[0], start[1]);
            range.setEnd(end[0], end[1]);

            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            return true;
        };

        // Поиск по открытому файлу: выделяет совпадение прямо в редакторе
        const initSearch = (toolbar, editor) => {
            const input = toolbar.querySelector('.js-editor-search');
            const counter = toolbar.querySelector('.js-editor-counter');
            let matches = [];
            let current = -1;
            let lastQuery = '';

            const collect = (query) => {
                matches = [];

                if (query === '') {
                    return;
                }

                const haystack = editor.textContent.toLowerCase();
                const needle = query.toLowerCase();
                let from = 0;

                while (true) {
                    const index = haystack.indexOf(needle, from);

                    if (index === -1) {
                        break;
                    }

                    matches.push(index);
                    from = index + needle.length;
                }
            };

            const select = (index) => {
                if (! selectRange(editor, matches[index], matches[index] + input.value.length)) {
                    return;
                }

                const line = editor.textContent.slice(0, matches[index]).split('\n').length;
                const lineHeight = parseFloat(window.getComputedStyle(editor).lineHeight) || 18;
                editor.scrollTop = Math.max(0, (line - 5) * lineHeight);
            };

            const step = (direction) => {
                if (input.value !== lastQuery) {
                    collect(input.value);
                    lastQuery = input.value;
                    current = -1;
                }

                if (matches.length === 0) {
                    counter.textContent = input.value === '' ? '' : '{{ __('page_editor::files.find_nothing') }}';

                    return;
                }

                current = (current + direction + matches.length) % matches.length;
                counter.textContent = (current + 1) + ' / ' + matches.length;
                select(current);
            };

            toolbar.querySelector('.js-editor-next').addEventListener('click', () => step(1));
            toolbar.querySelector('.js-editor-prev').addEventListener('click', () => step(-1));

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    step(event.shiftKey ? -1 : 1);
                }
            });
        };

        const scrollToLine = (element) => {
            if (line > 1) {
                const lineHeight = parseFloat(window.getComputedStyle(element).lineHeight) || 18;
                element.scrollTop = Math.max(0, (line - 3) * lineHeight);
            }
        };

        // Переход из поиска по коду: строка выделяется, а не только подкручивается.
        // Короткий файл прокручивать некуда, и без выделения непонятно, куда вёл результат
        const gotoLine = (element) => {
            const lines = element.textContent.split('\n');

            if (line < 1 || line > lines.length) {
                return;
            }

            let offset = 0;

            for (let i = 0; i < line - 1; i++) {
                offset += lines[i].length + 1;
            }

            selectRange(element, offset, offset + lines[line - 1].length);
            scrollToLine(element);
        };

        if (textarea && language !== 'none' && window.Prism) {
            const editor = document.createElement('div');
            editor.className = 'form-control font-monospace js-code-editor language-' + language;
            editor.textContent = textarea.value;
            textarea.after(editor);
            textarea.hidden = true;

            const highlight = (element) => window.Prism.highlightElement(element);

            const toolbar = document.querySelector('.js-editor-toolbar');

            if (textarea.readOnly) {
                highlight(editor);
            } else {
                // Своя история: у CodeJar нет методов отмены, только горячие клавиши,
                // а кнопки и клавиши должны вести себя одинаково
                const jar = CodeJar(editor, highlight, { tab: '    ', history: false });
                const undoButton = toolbar?.querySelector('.js-editor-undo');
                const redoButton = toolbar?.querySelector('.js-editor-redo');

                const history = [textarea.value];
                let at = 0;
                let timer = null;

                const refresh = () => {
                    if (undoButton) undoButton.disabled = at <= 0;
                    if (redoButton) redoButton.disabled = at >= history.length - 1;
                };

                const apply = (step) => {
                    const next = at + step;

                    if (next < 0 || next >= history.length) {
                        return;
                    }

                    at = next;
                    jar.updateCode(history[at]);
                    textarea.value = history[at];
                    refresh();
                };

                jar.updateCode(textarea.value);

                jar.onUpdate((code) => {
                    textarea.value = code;

                    // Снимок через паузу: иначе история распухнет от каждого символа
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        if (history[at] === code) {
                            return;
                        }

                        history.splice(at + 1);
                        history.push(code);
                        at = history.length - 1;
                        refresh();
                    }, 400);
                });

                undoButton?.addEventListener('click', () => apply(-1));
                redoButton?.addEventListener('click', () => apply(1));

                editor.addEventListener('keydown', (event) => {
                    if (! (event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 'z') {
                        return;
                    }

                    event.preventDefault();
                    apply(event.shiftKey ? 1 : -1);
                });

                // Забираем текст ещё и перед отправкой: не полагаемся на то,
                // что колбэк успел отработать на последнем нажатии
                textarea.form?.addEventListener('submit', () => {
                    textarea.value = jar.toString();
                });

                refresh();
            }

            if (toolbar) {
                toolbar.hidden = false;
                initSearch(toolbar, editor);
            }

            gotoLine(editor);
        } else if (textarea) {
            // Двоичные и незнакомые расширения правятся обычной textarea
            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                    this.selectionStart = this.selectionEnd = start + 4;
                }
            });

            if (line > 0) {
                const lines = textarea.value.split('\n');
                let offset = 0;

                for (let i = 0; i < line - 1 && i < lines.length; i++) {
                    offset += lines[i].length + 1;
                }

                textarea.focus();
                textarea.setSelectionRange(offset, offset + (lines[line - 1] || '').length);
                scrollToLine(textarea);
            }
        }
    </script>
@endpush
@stop
