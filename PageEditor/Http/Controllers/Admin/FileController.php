<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PageEditor\Http\Requests\FileCreateRequest;
use Modules\PageEditor\Http\Requests\FileUploadRequest;
use Modules\PageEditor\Support\FileWriter;
use Modules\PageEditor\Support\OverrideResolver;
use Modules\PageEditor\Support\PathResolver;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    private string $root;
    private string $path;
    private string $file;

    /**
     * Конструктор
     */
    public function __construct(Request $request)
    {
        $root = $request->input('root', 'views');
        $path = $request->input('path', '');
        $file = $request->input('file', '');

        $this->root = is_string($root) ? $root : 'views';
        $this->path = PathResolver::normalize(is_string($path) ? $path : '');
        $this->file = PathResolver::normalize(is_string($file) ? $file : '');

        // Корень, доступный только поиску, открывается на просмотр: сюда ведут
        // результаты поиска. Всё, что меняет файлы, закрыто ниже
        if (! isset(PathResolver::searchRoots()[$this->root])) {
            abort(404);
        }
    }

    /**
     * Запрещает изменения в корне, доступном только для чтения
     */
    private function denyReadOnly(): void
    {
        if (PathResolver::isReadOnly($this->root)) {
            abort(404);
        }
    }

    /**
     * Сообщает, что имя принадлежит каталогу модуля
     *
     * Модуль удаляется и переименовывается только из админки модулей: там
     * снимаются настройки, таблицы и хуки. Здесь за крестиком осталась бы
     * установленная запись без файлов
     */
    private function isModuleDirectory(string $name): bool
    {
        return $this->root === 'modules'
            && $this->path === ''
            && $name !== ''
            && is_dir(PathResolver::resolve($this->root, $name));
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        $this->denyReadOnly();

        $directory = PathResolver::resolve($this->root, $this->path);

        // Каталог custom появляется только после первой правки:
        // пустой каталог показываем как пустой список, а не как ошибку
        if (! is_dir($directory) && $this->path !== '') {
            abort(404, __('page_editor::files.directory_not_exist'));
        }

        /** @var list<array{name: string, dir: bool, size: int, lines: int, mtime: int, editable: bool, module: bool, disabled: bool, overridden: bool}> $entries */
        $entries = [];
        $maxSize = (int) config('page_editor.max_edit_size', 1048576);

        $names = is_dir($directory) ? (preg_grep('/^([^.])/', scandir($directory, SCANDIR_SORT_ASCENDING) ?: []) ?: []) : [];

        // В корне modules каталог верхнего уровня — это модуль: помечаем выключенные,
        // чтобы правка файлов без эффекта не выглядела поломкой
        $modules = $this->root === 'modules' && $this->path === ''
            ? array_keys(Module::getEnabledModules())
            : null;

        // Переопределяются только шаблоны и переводы, для остальных корней проверку не делаем
        $overridable = in_array($this->root, ['views', 'lang', 'modules'], true);

        foreach ($names as $name) {
            $full = $directory . '/' . $name;
            $isDir = is_dir($full);
            $editable = ! $isDir && PathResolver::isEditable($name);

            $entries[] = [
                'name' => $name,
                'dir'  => $isDir,
                // Счёт по тому же фильтру, что и листинг: скрытые файлы не показываются,
                // иначе каталог с одним .gitignore выглядел бы непустым
                'size' => $isDir ? count(preg_grep('/^([^.])/', scandir($full)) ?: []) : (int) filesize($full),
                // Строки считаем только у небольших редактируемых файлов: в корне assets
                // лежат шрифты, картинки и бандлы — file() затянул бы их целиком в память
                'lines'    => $editable && filesize($full) <= $maxSize ? count(file($full) ?: []) : 0,
                'mtime'    => (int) filemtime($full),
                'editable' => $editable,
                'module'   => $modules !== null && $isDir,
                'disabled' => $modules !== null && $isDir && ! in_array($name, $modules, true),
                // Правка в custom перебивает оригинал, поэтому её видно ещё в списке
                'overridden' => ! $isDir && $overridable
                    && (OverrideResolver::override($this->root, $this->path, $name)['exists'] ?? false),
                // Обратный случай: оригинал переименован или удалён, и правка уже ничего
                // не подменяет. Без пометки это видно, только открыв файл
                'orphan' => ! $isDir && $this->root === 'custom'
                    && ($original = OverrideResolver::original($this->path, $name)) !== null
                    && ! $original['exists'],
            ];
        }

        usort($entries, static fn (array $a, array $b) => [! $a['dir'], $a['name']] <=> [! $b['dir'], $b['name']]);

        $root = $this->root;
        $roots = array_keys(PathResolver::roots());
        $path = $this->path;

        // Поиск работает не по всем корням: в assets лежат картинки и шрифты, искать
        // в них нечего. Раньше поле молча уходило в первый корень из списка, то есть
        // из «Ресурсов» искало по шаблонам — теперь его там просто нет
        $searchRoot = in_array($root, config('page_editor.search_roots', []), true) ? $root : null;

        return view('page_editor::admin/files/index', compact('entries', 'root', 'roots', 'path', 'searchRoot'));
    }

    /**
     * Редактирование файла
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $full = $this->editableFile();

        // Крупный .json или .svg целиком в память не читаем — вернём в листинг
        if (filesize($full) > (int) config('page_editor.max_edit_size', 1048576)) {
            return redirect()->route('admin.files.index', ['root' => $this->root, 'path' => $this->path])
                ->with('danger', __('page_editor::files.file_too_large'));
        }

        $contest = file_get_contents($full);
        $writable = is_writable($full);
        $root = $this->root;
        $path = $this->path;
        $file = $this->file;
        $line = $request->integer('line');

        // Плашка над редактором: какой слой открыт и где лежит второй
        $override = $this->root === 'custom' ? null : OverrideResolver::override($this->root, $this->path, $this->file);
        $original = $this->root === 'custom' ? OverrideResolver::original($this->path, $this->file) : null;

        $readOnly = PathResolver::isReadOnly($this->root);

        // Грамматика подсветки: blade и php идут одной, остальное — по расширению
        $language = match (PathResolver::extension($this->file)) {
            'php', 'blade.php'   => 'php',
            'css', 'scss'        => 'css',
            'js', 'json'         => 'javascript',
            'xml', 'svg', 'html' => 'markup',
            default              => 'none',
        };

        return view('page_editor::admin/files/edit', compact(
            'contest',
            'root',
            'path',
            'file',
            'writable',
            'line',
            'override',
            'original',
            'readOnly',
            'language',
        ));
    }

    /**
     * Сохранение файла
     */
    public function update(Request $request): RedirectResponse
    {
        $this->denyReadOnly();

        $full = $this->editableFile();
        $params = ['root' => $this->root, 'path' => $this->path, 'file' => $this->file];

        // Вторая кнопка формы: содержимое уходит в custom, оригинал остаётся нетронутым
        $toOverride = $request->input('target') === 'custom'
            && $this->root !== 'custom'
            && ($override = OverrideResolver::override($this->root, $this->path, $this->file)) !== null;

        if ($toOverride) {
            $target = PathResolver::resolve($override['root'], trim($override['path'] . '/' . $override['file'], '/'));
            $params = ['root' => $override['root'], 'path' => $override['path'], 'file' => $override['file']];
            $status = __('page_editor::files.override_saved');
        } else {
            if (! is_writable($full)) {
                return redirect()->route('admin.files.edit', $params)
                    ->withInput()
                    ->withErrors(['msg' => __('page_editor::files.writable')]);
            }

            $target = $full;
            $status = __('page_editor::files.file_success_saved');
        }

        try {
            FileWriter::put($target, self::normalizeContent($request->string('msg')->value()));
        } catch (RuntimeException) {
            return redirect()->route('admin.files.edit', $params)
                ->withInput()
                ->with('danger', __('page_editor::files.file_write_failed'));
        }

        return redirect()->route('admin.files.edit', $params)
            ->with('success', $status);
    }

    /**
     * Создание файла
     */
    public function create(): View
    {
        $this->writableDirectory(create: true);

        return view('page_editor::admin/files/create', ['root' => $this->root, 'path' => $this->path]);
    }

    /**
     * Сохранение нового файла или каталога
     */
    public function store(FileCreateRequest $request): RedirectResponse
    {
        $directory = $this->writableDirectory(create: true);
        $filename = (string) $request->validated('filename');
        $dirname = (string) $request->validated('dirname');
        $field = $filename !== '' ? 'filename' : 'dirname';

        if (file_exists($directory . '/' . ($filename ?: $dirname))) {
            return redirect()->route('admin.files.create', ['root' => $this->root, 'path' => $this->path])
                ->withInput()
                ->withErrors([$field => __($field === 'filename' ? 'page_editor::files.file_exist' : 'page_editor::files.directory_exist')]);
        }

        if ($filename !== '') {
            try {
                FileWriter::put($directory . '/' . $filename, '');
            } catch (RuntimeException) {
                return redirect()->route('admin.files.create', ['root' => $this->root, 'path' => $this->path])
                    ->withInput()
                    ->with('danger', __('page_editor::files.file_write_failed'));
            }

            chmod($directory . '/' . $filename, 0644);

            return redirect()->route('admin.files.edit', [
                'root' => $this->root,
                'path' => $this->path,
                'file' => $filename,
            ])->with('success', __('page_editor::files.file_success_created'));
        }

        $old = umask(0);
        $created = mkdir($target = $directory . '/' . $dirname, 0755, true) || is_dir($target);
        umask($old);

        $flash = $created
            ? ['success', __('page_editor::files.directory_success_created')]
            : ['danger', __('page_editor::files.directory_not_writable', ['dir' => $dirname])];

        return redirect()->route('admin.files.index', [
            'root' => $this->root,
            'path' => trim($this->path . '/' . $dirname, '/'),
        ])->with(...$flash);
    }

    /**
     * Загрузка файла
     */
    public function upload(FileUploadRequest $request): RedirectResponse
    {
        $directory = $this->writableDirectory();
        $params = ['root' => $this->root, 'path' => $this->path];
        $name = $request->fileName();

        if (file_exists($directory . '/' . $name)) {
            return redirect()->route('admin.files.create', $params)
                ->withErrors(['file' => __('page_editor::files.file_exist')]);
        }

        $request->file('file')->move($directory, $name);
        chmod($directory . '/' . $name, 0644);

        return redirect()->route('admin.files.index', $params)
            ->with('success', __('page_editor::files.file_success_uploaded'));
    }

    /**
     * Удаление файла
     */
    public function delete(Request $request): RedirectResponse
    {
        $directory = $this->writableDirectory();
        $params = ['root' => $this->root, 'path' => $this->path];

        $filename = PathResolver::normalize($request->string('filename')->value());
        $dirname = PathResolver::normalize($request->string('dirname')->value());

        // Пустое имя обязано отсекаться: is_file/is_dir от "<каталог>/" дают true,
        // и удаление ушло бы на сам текущий каталог
        $error = match (true) {
            $filename !== ''                                         => is_file($directory . '/' . $filename) ? null : __('page_editor::files.file_not_exist'),
            $dirname === '' || ! is_dir($directory . '/' . $dirname) => __('page_editor::files.directory_not_exist'),
            $this->isModuleDirectory($dirname)                       => __('page_editor::files.module_directory_protected'),
            default                                                  => null,
        };

        if ($error) {
            return redirect()->route('admin.files.index', $params)->withErrors($error);
        }

        if ($filename !== '') {
            try {
                FileWriter::delete($directory . '/' . $filename);
            } catch (RuntimeException) {
                return redirect()->route('admin.files.index', $params)
                    ->with('danger', __('page_editor::files.file_delete_failed'));
            }

            $status = __('page_editor::files.file_success_deleted');
        } else {
            deleteDir($directory . '/' . $dirname);
            $status = __('page_editor::files.directory_success_deleted');
        }

        return redirect()->route('admin.files.index', $params)
            ->with('success', $status);
    }

    /**
     * Переименование файла или директории
     */
    public function rename(Request $request): RedirectResponse
    {
        $directory = $this->writableDirectory();
        $params = ['root' => $this->root, 'path' => $this->path];

        $name = PathResolver::normalize($request->string('filename')->value());
        $newName = PathResolver::normalize($request->string('newname')->value());

        // Расширение проверяем той же меркой, что и при создании: иначе файл уезжает
        // в нередактируемые, и открыть его, чтобы вернуть имя, уже нельзя
        $error = match (true) {
            $name === '' || ! file_exists($directory . '/' . $name)                   => __('page_editor::files.file_not_exist'),
            ! preg_match(FileCreateRequest::NAME_PATTERN, $newName)                   => __('page_editor::files.file_invalid'),
            file_exists($directory . '/' . $newName)                                  => __('page_editor::files.file_exist'),
            $this->isModuleDirectory($name)                                           => __('page_editor::files.module_directory_protected'),
            is_file($directory . '/' . $name) && ! PathResolver::isEditable($newName) => __('page_editor::files.file_invalid'),
            default                                                                   => null,
        };

        if ($error) {
            return redirect()->route('admin.files.index', $params)->withErrors($error);
        }

        rename($directory . '/' . $name, $directory . '/' . $newName);

        return redirect()->route('admin.files.index', $params)
            ->with('success', __('page_editor::files.file_success_renamed'));
    }

    /**
     * Скачивание файла
     */
    public function download(): BinaryFileResponse
    {
        $this->denyReadOnly();

        $full = PathResolver::resolve($this->root, trim($this->path . '/' . $this->file, '/'));

        if ($this->file === '' || ! is_file($full)) {
            abort(404, __('page_editor::files.file_not_exist'));
        }

        // Имя вложения без каталогов: со слэшем HeaderUtils::makeDisposition бросает исключение
        return response()->download($full, basename($this->file));
    }

    /**
     * Редактируемый файл из запроса
     */
    private function editableFile(): string
    {
        if ($this->file === '' || ! PathResolver::isEditable($this->file)) {
            abort(404, __('page_editor::files.file_invalid'));
        }

        $full = PathResolver::resolve($this->root, trim($this->path . '/' . $this->file, '/'));

        if (! is_file($full)) {
            abort(404, __('page_editor::files.file_not_exist'));
        }

        return $full;
    }

    /**
     * Текущий каталог, открытый на запись
     *
     * Каталог корня создаётся по требованию: custom до первой правки
     * перевода не существует, но создать в нём файл должно быть можно.
     * Вложенные пути не создаём — их заводят кнопкой «создать директорию»
     */
    private function writableDirectory(bool $create = false): string
    {
        $this->denyReadOnly();

        $directory = PathResolver::resolve($this->root, $this->path);

        if ($create && $this->path === '' && ! is_dir($directory)) {
            $old = umask(0);
            @mkdir($directory, 0755, true);
            umask($old);
        }

        if (! is_dir($directory) || ! is_writable($directory)) {
            abort(500, __('page_editor::files.directory_not_writable', ['dir' => $this->path ?: $this->root]));
        }

        return $directory;
    }

    /**
     * Приводит содержимое из формы к виду обычного текстового файла
     *
     * Браузер отправляет переводы строк как CRLF, а завершающий перевод строки
     * до контроллера не доезжает: TrimStrings обрезает поля запроса
     */
    private static function normalizeContent(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return rtrim($content, "\n") . "\n";
    }
}
