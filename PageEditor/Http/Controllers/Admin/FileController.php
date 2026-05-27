<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Controllers\Admin;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FileController extends Controller
{
    private string $file;
    private ?string $path;

    /**
     * Конструктор
     */
    public function __construct(Request $request)
    {
        $this->file = ltrim(check($request->input('file')), '/');
        $this->path = rtrim(check($request->input('path')), '/');

        if (
            empty($this->path)
            || Str::contains($this->path, '.')
            || Str::startsWith($this->path, '/')
            || ! file_exists(resource_path('views/' . $this->path))
            || ! is_dir(resource_path('views/' . $this->path))
        ) {
            $this->path = null;
        }
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        $path = $this->path;
        $elements = preg_grep('/^([^.])/', scandir(resource_path('views/' . $path . $this->file), SCANDIR_SORT_ASCENDING));

        $folders = [];
        $files = [];

        foreach ($elements as $element) {
            if (is_dir(resource_path('views/' . $path . '/' . $element))) {
                $folders[] = $element;
            } else {
                $files[] = $element;
            }
        }

        $files = array_merge($folders, $files);

        $directories = explode('/', (string) $path);

        return view('page_editor::admin/files/index', compact('files', 'path', 'directories'));
    }

    /**
     * Редактирование файла
     */
    public function edit(Request $request, Validator $validator): View|RedirectResponse
    {
        $path = $this->path;
        $file = $path ? '/' . $this->file : $this->file;
        $writable = is_writable(resource_path('views/' . $path . $file . '.blade.php'));

        if (
            ($this->path && ! preg_match('#^([a-z0-9_\-/]+|)$#', $this->path))
            || ! preg_match('#^[a-z0-9_\-/]+$#', $this->file)
        ) {
            abort(404, __('page_editor::files.file_invalid'));
        }

        if (! file_exists(resource_path('views/' . $this->path . $file . '.blade.php'))) {
            abort(404, __('page_editor::files.file_not_exist'));
        }

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator->true($writable, ['msg' => __('page_editor::files.writable')]);

            if ($validator->isValid()) {
                file_put_contents(resource_path('views/' . $this->path . $file . '.blade.php'), $msg);

                setFlash('success', __('page_editor::files.file_success_saved'));

                return redirect('admin/files/edit?path=' . $this->path . '&file=' . $this->file);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $contest = file_get_contents(resource_path('views/' . $path . $file . '.blade.php'));

        return view('page_editor::admin/files/edit', compact('contest', 'path', 'file', 'writable'));
    }

    /**
     * Создание файла
     */
    public function create(Request $request, Validator $validator): View|RedirectResponse
    {
        if (! is_writable(resource_path('views/' . $this->path))) {
            abort(200, __('page_editor::files.directory_not_writable', ['dir' => $this->path]));
        }

        if ($request->isMethod('post')) {
            $filename = check($request->input('filename'));
            $dirname = check($request->input('dirname'));

            $fileName = $this->path ? '/' . $filename : $filename;
            $dirName = $this->path ? '/' . $dirname : $dirname;

            if ($filename) {
                $validator->length($filename, 1, 30, ['filename' => __('page_editor::files.file_required')]);
                $validator->false(file_exists(resource_path('views/' . $this->path . $fileName . '.blade.php')), ['filename' => __('page_editor::files.file_exist')]);
                $validator->regex($filename, '|^[a-z0-9_\-]+$|', ['filename' => __('page_editor::files.file_invalid')]);
            } else {
                $validator->length($dirname, 1, 30, ['dirname' => __('page_editor::files.directory_required')]);
                $validator->false(file_exists(resource_path('views/' . $this->path . $dirName)), ['dirname' => __('page_editor::files.directory_exist')]);
                $validator->regex($dirname, '|^[a-z0-9_\-]+$|', ['dirname' => __('page_editor::files.directory_invalid')]);
            }

            if ($validator->isValid()) {
                if ($filename) {
                    file_put_contents(resource_path('views/' . $this->path . $fileName . '.blade.php'), '');
                    chmod(resource_path('views/' . $this->path . $fileName . '.blade.php'), 0644);

                    setFlash('success', __('page_editor::files.file_success_created'));

                    return redirect('admin/files/edit?path=' . $this->path . '&file=' . $filename);
                }

                $old = umask(0);
                if (! mkdir($directory = resource_path('views/' . $this->path . $dirName), 0755, true) && ! is_dir($directory)) {
                    $flash = ['danger', 'Directory "%s" was not created', $directory];
                } else {
                    $flash = ['success', __('page_editor::files.directory_success_created')];
                }

                umask($old);

                return redirect('admin/files?path=' . $this->path . $dirName)->with(...$flash);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('page_editor::admin/files/create', ['path' => $this->path]);
    }

    /**
     * Удаление файла
     */
    public function delete(Request $request, Validator $validator): RedirectResponse
    {
        if (! is_writable(resource_path('views/' . $this->path))) {
            abort(200, __('page_editor::files.directory_not_writable', ['dir' => $this->path]));
        }

        $filename = check($request->input('filename'));
        $dirname = check($request->input('dirname'));

        $fileName = $this->path ? '/' . $filename : $filename;
        $dirName = $this->path ? '/' . $dirname : $dirname;

        if ($filename) {
            $validator->true(file_exists(resource_path('views/' . $this->path . $fileName . '.blade.php')), __('page_editor::files.file_not_exist'));
            $validator->regex($filename, '|^[a-z0-9_\-]+$|', __('page_editor::files.file_invalid'));
        } else {
            $validator->true(file_exists(resource_path('views/' . $this->path . $dirName)), __('page_editor::files.directory_not_exist'));
            $validator->regex($dirname, '|^[a-z0-9_\-]+$|', __('page_editor::files.directory_invalid'));
        }

        if ($validator->isValid()) {
            if ($filename) {
                unlink(resource_path('views/' . $this->path . $fileName . '.blade.php'));
                setFlash('success', __('page_editor::files.file_success_deleted'));
            } else {
                deleteDir(resource_path('views/' . $this->path . $dirName));
                setFlash('success', __('page_editor::files.directory_success_deleted'));
            }
        } else {
            setFlash('danger', $validator->getErrors());
        }

        return redirect('admin/files?path=' . $this->path);
    }
}
