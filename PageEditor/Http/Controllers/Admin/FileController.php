<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PageEditor\Support\FileWriter;
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
        $this->root = (string) $request->input('root', 'views');
        $this->path = PathResolver::normalize((string) $request->input('path', ''));
        $this->file = PathResolver::normalize((string) $request->input('file', ''));

        if (! isset(PathResolver::roots()[$this->root])) {
            abort(404);
        }
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        $directory = PathResolver::resolve($this->root, $this->path);

        // Каталог custom появляется только после первой правки:
        // пустой каталог показываем как пустой список, а не как ошибку
        if (! is_dir($directory) && $this->path !== '') {
            abort(404, __('page_editor::files.directory_not_exist'));
        }

        /** @var list<array{name: string, dir: bool, size: int, lines: int, mtime: int, editable: bool, disabled: bool}> $entries */
        $entries = [];
        $maxSize = (int) config('page_editor.max_edit_size', 1048576);

        $names = is_dir($directory) ? preg_grep('/^([^.])/', scandir($directory, SCANDIR_SORT_ASCENDING)) : [];

        // В корне modules каталог верхнего уровня — это модуль: помечаем выключенные,
        // чтобы правка файлов без эффекта не выглядела поломкой
        $modules = $this->root === 'modules' && $this->path === ''
            ? array_keys(Module::getEnabledModules())
            : null;

        foreach ($names as $name) {
            $full = $directory . '/' . $name;
            $isDir = is_dir($full);
            $editable = ! $isDir && PathResolver::isEditable($name);

            $entries[] = [
                'name' => $name,
                'dir'  => $isDir,
                'size' => $isDir ? count(array_diff(scandir($full), ['.', '..'])) : (int) filesize($full),
                // Строки считаем только у небольших редактируемых файлов: в корне assets
                // лежат шрифты, картинки и бандлы — file() затянул бы их целиком в память
                'lines'    => $editable && filesize($full) <= $maxSize ? count(file($full) ?: []) : 0,
                'mtime'    => (int) filemtime($full),
                'editable' => $editable,
                'disabled' => $modules !== null && $isDir && ! in_array($name, $modules, true),
            ];
        }

        usort($entries, static fn (array $a, array $b) => [! $a['dir'], $a['name']] <=> [! $b['dir'], $b['name']]);

        $root = $this->root;
        $roots = array_keys(PathResolver::roots());
        $path = $this->path;

        // Поиск работает не по всем корням, поэтому строка поиска на листинге
        // уходит в текущий корень только если он разрешён для поиска
        $searchRoots = config('page_editor.search_roots', []);
        $searchRoot = in_array($root, $searchRoots, true) ? $root : ($searchRoots[0] ?? null);

        return view('page_editor::admin/files/index', compact('entries', 'root', 'roots', 'path', 'searchRoot'));
    }

    /**
     * Редактирование файла
     */
    public function edit(Request $request, Validator $validator): View|RedirectResponse
    {
        $full = PathResolver::resolve($this->root, trim($this->path . '/' . $this->file, '/'));

        if ($this->file === '' || ! PathResolver::isEditable($this->file)) {
            abort(404, __('page_editor::files.file_invalid'));
        }

        if (! is_file($full)) {
            abort(404, __('page_editor::files.file_not_exist'));
        }

        $writable = is_writable($full);
        $params = ['root' => $this->root, 'path' => $this->path, 'file' => $this->file];

        if ($request->isMethod('post')) {
            $validator->true($writable, ['msg' => __('page_editor::files.writable')]);

            if ($validator->isValid()) {
                try {
                    FileWriter::put($full, (string) $request->input('msg'));
                } catch (RuntimeException) {
                    return redirect()->route('admin.files.edit', $params)
                        ->withInput()
                        ->with('flash.danger', __('page_editor::files.file_write_failed'));
                }

                return redirect()->route('admin.files.edit', $params)
                    ->with('flash.success', __('page_editor::files.file_success_saved'));
            }

            return redirect()->route('admin.files.edit', $params)
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        // Крупный .json или .svg целиком в память не читаем — вернём в листинг
        if (filesize($full) > (int) config('page_editor.max_edit_size', 1048576)) {
            return redirect()->route('admin.files.index', ['root' => $this->root, 'path' => $this->path])
                ->with('flash.danger', __('page_editor::files.file_too_large'));
        }

        $contest = file_get_contents($full);
        $root = $this->root;
        $path = $this->path;
        $file = $this->file;
        $line = (int) $request->input('line');

        return view('page_editor::admin/files/edit', compact('contest', 'root', 'path', 'file', 'writable', 'line'));
    }

    /**
     * Создание файла
     */
    public function create(Request $request, Validator $validator): View|RedirectResponse
    {
        $directory = PathResolver::resolve($this->root, $this->path);

        // Каталог корня создаём по требованию: custom до первой правки
        // перевода не существует, но создать в нём файл должно быть можно.
        // Вложенные пути не создаём — их заводят кнопкой «создать директорию»
        if ($this->path === '' && ! is_dir($directory)) {
            $old = umask(0);
            @mkdir($directory, 0755, true);
            umask($old);
        }

        if (! is_dir($directory) || ! is_writable($directory)) {
            abort(200, __('page_editor::files.directory_not_writable', ['dir' => $this->path ?: $this->root]));
        }

        if ($request->isMethod('post')) {
            $filename = PathResolver::normalize((string) $request->input('filename'));
            $dirname = PathResolver::normalize((string) $request->input('dirname'));
            $pattern = '|^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*$|i';

            if ($filename !== '') {
                $validator->length($filename, 1, 255, ['filename' => __('page_editor::files.file_required')]);
                $validator->regex($filename, $pattern, ['filename' => __('page_editor::files.file_invalid')]);
                $validator->true(PathResolver::isEditable($filename), ['filename' => __('page_editor::files.file_invalid')]);
                $validator->false(file_exists($directory . '/' . $filename), ['filename' => __('page_editor::files.file_exist')]);
            } else {
                $validator->length($dirname, 1, 255, ['dirname' => __('page_editor::files.directory_required')]);
                $validator->regex($dirname, $pattern, ['dirname' => __('page_editor::files.directory_invalid')]);
                $validator->false(file_exists($directory . '/' . $dirname), ['dirname' => __('page_editor::files.directory_exist')]);
            }

            if ($validator->isValid()) {
                if ($filename !== '') {
                    try {
                        FileWriter::put($directory . '/' . $filename, '');
                    } catch (RuntimeException) {
                        return redirect()->route('admin.files.create', ['root' => $this->root, 'path' => $this->path])
                            ->withInput()
                            ->with('flash.danger', __('page_editor::files.file_write_failed'));
                    }

                    chmod($directory . '/' . $filename, 0644);

                    return redirect()->route('admin.files.edit', [
                        'root' => $this->root,
                        'path' => $this->path,
                        'file' => $filename,
                    ])->with('flash.success', __('page_editor::files.file_success_created'));
                }

                $old = umask(0);
                $created = mkdir($target = $directory . '/' . $dirname, 0755, true) || is_dir($target);
                umask($old);

                $flash = $created
                    ? ['flash.success', __('page_editor::files.directory_success_created')]
                    : ['flash.danger', __('page_editor::files.directory_not_writable', ['dir' => $dirname])];

                return redirect()->route('admin.files.index', [
                    'root' => $this->root,
                    'path' => trim($this->path . '/' . $dirname, '/'),
                ])->with(...$flash);
            }

            return redirect()->route('admin.files.create', ['root' => $this->root, 'path' => $this->path])
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        return view('page_editor::admin/files/create', ['root' => $this->root, 'path' => $this->path]);
    }

    /**
     * Удаление файла
     */
    public function delete(Request $request, Validator $validator): RedirectResponse
    {
        $directory = PathResolver::resolve($this->root, $this->path);
        $params = ['root' => $this->root, 'path' => $this->path];

        if (! is_writable($directory)) {
            abort(200, __('page_editor::files.directory_not_writable', ['dir' => $this->path ?: $this->root]));
        }

        $filename = PathResolver::normalize((string) $request->input('filename'));
        $dirname = PathResolver::normalize((string) $request->input('dirname'));

        // Пустое имя обязано отсекаться: is_file/is_dir от "<каталог>/" дают true,
        // и удаление ушло бы на сам текущий каталог
        if ($filename !== '') {
            $validator->true(is_file($directory . '/' . $filename), __('page_editor::files.file_not_exist'));
        } else {
            $validator->true($dirname !== '' && is_dir($directory . '/' . $dirname), __('page_editor::files.directory_not_exist'));
        }

        if (! $validator->isValid()) {
            return redirect()->route('admin.files.index', $params)
                ->withErrors($validator->getErrors());
        }

        if ($filename !== '') {
            try {
                FileWriter::delete($directory . '/' . $filename);
            } catch (RuntimeException) {
                return redirect()->route('admin.files.index', $params)
                    ->with('flash.danger', __('page_editor::files.file_delete_failed'));
            }

            $status = __('page_editor::files.file_success_deleted');
        } else {
            deleteDir($directory . '/' . $dirname);
            $status = __('page_editor::files.directory_success_deleted');
        }

        return redirect()->route('admin.files.index', $params)
            ->with('flash.success', $status);
    }

    /**
     * Переименование файла или директории
     */
    public function rename(Request $request, Validator $validator): RedirectResponse
    {
        $directory = PathResolver::resolve($this->root, $this->path);
        $params = ['root' => $this->root, 'path' => $this->path];

        if (! is_writable($directory)) {
            abort(200, __('page_editor::files.directory_not_writable', ['dir' => $this->path ?: $this->root]));
        }

        $name = PathResolver::normalize((string) $request->input('filename'));
        $newName = PathResolver::normalize((string) $request->input('newname'));

        $validator->true($name !== '' && file_exists($directory . '/' . $name), __('page_editor::files.file_not_exist'));
        $validator->regex($newName, '|^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*$|i', __('page_editor::files.file_invalid'));
        $validator->false(file_exists($directory . '/' . $newName), __('page_editor::files.file_exist'));

        if (! $validator->isValid()) {
            return redirect()->route('admin.files.index', $params)
                ->withErrors($validator->getErrors());
        }

        rename($directory . '/' . $name, $directory . '/' . $newName);

        return redirect()->route('admin.files.index', $params)
            ->with('flash.success', __('page_editor::files.file_success_renamed'));
    }

    /**
     * Скачивание файла
     */
    public function download(): BinaryFileResponse
    {
        $full = PathResolver::resolve($this->root, trim($this->path . '/' . $this->file, '/'));

        if ($this->file === '' || ! is_file($full)) {
            abort(404, __('page_editor::files.file_not_exist'));
        }

        // Имя вложения без каталогов: со слэшем HeaderUtils::makeDisposition бросает исключение
        return response()->download($full, basename($this->file));
    }
}
