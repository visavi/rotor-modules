/**
 * Номера строк слева от редактора, как на GitHub: отдельная колонка,
 * не выделяется и не попадает в копируемый текст.
 *
 * Возвращает обёртку над функцией подсветки: номера пересчитываются
 * при каждой перерисовке кода.
 */
export function withLineNumbers(editor, highlight) {
    const wrap = document.createElement('div');
    wrap.className = 'code-editor-wrap';

    const gutter = document.createElement('div');
    gutter.className = 'code-editor-gutter';
    gutter.setAttribute('aria-hidden', 'true');

    const numbers = document.createElement('div');
    gutter.appendChild(numbers);

    editor.before(wrap);
    wrap.append(gutter, editor);

    const style = window.getComputedStyle(editor);
    const basePadding = style.paddingLeft;
    gutter.style.paddingTop = style.paddingTop;
    gutter.style.fontSize = style.fontSize;
    gutter.style.lineHeight = style.lineHeight;

    let count = 0;

    // Колонка не заходит на горизонтальную полосу прокрутки и едет вместе с кодом по вертикали
    const sync = () => {
        const border = parseFloat(style.borderTopWidth) || 0;
        gutter.style.bottom = (editor.offsetHeight - editor.clientHeight - border) + 'px';
        numbers.style.transform = 'translateY(' + -editor.scrollTop + 'px)';
    };

    const render = () => {
        // CodeJar ставит inline pre-wrap: перенесённая строка заняла бы несколько рядов под одним номером
        editor.style.whiteSpace = 'pre';

        // Перевод строки в конце файла не даёт видимой пустой строки
        const total = editor.textContent.replace(/\n$/, '').split('\n').length;

        if (total !== count) {
            count = total;
            numbers.textContent = Array.from({ length: total }, (_, i) => i + 1).join('\n');

            const width = 'calc(' + String(total).length + 'ch + 1.5rem)';
            gutter.style.width = width;
            editor.style.paddingLeft = 'calc(' + width + ' + ' + basePadding + ')';
        }

        sync();
    };

    editor.addEventListener('scroll', sync, { passive: true });
    new ResizeObserver(sync).observe(editor);

    return (element) => {
        highlight(element);
        render();
    };
}
