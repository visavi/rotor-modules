<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Load;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Models\Reader;
use App\Models\User;
use App\Traits\HandlesComments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Load\Http\Requests\DownStoreRequest;
use Modules\Load\Http\Requests\OwnDownRequest;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Modules\Load\Services\DownService;
use Modules\Load\Services\ZipMap;
use Modules\Load\Services\ZipTree;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class DownController extends Controller
{
    use HandlesComments;

    /**
     * Модель для комментариев
     */
    protected string $commentableModelClass = Down::class;

    /**
     * Сколько браузер держит картинку из архива
     */
    private const int ZIP_CACHE_MAX_AGE = 86400;

    /**
     * Просмотр загрузки
     */
    public function view(int $id): View
    {
        $down = Down::query()
            ->where('id', $id)
            ->with('category.parent', 'poll')
            ->first();

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        // Непроверенная загрузка доступна только автору и администрации, как и в API
        if (! $down->active && ! isAdmin(User::ADMIN) && getUser('id') !== $down->user_id) {
            abort(200, __('load::loads.down_not_verified'));
        }

        $allowDownload = getUser() || setting('down_guest_download');

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($down);

        return view('load::downs/down', compact('down', 'allowDownload', 'comments', 'files'));
    }

    /**
     * Создание загрузки
     */
    public function create(Request $request): View
    {
        $this->checkUploadOpen();

        $categories = (new Load())->getChildren();

        if ($categories->isEmpty()) {
            abort(200, __('load::loads.empty_loads'));
        }

        $cid = int($request->input('category'));
        $down = new Down();

        $files = File::query()
            ->pending(Down::$morphName, getUser('id'))
            ->ordered()
            ->get();

        return view('load::downs/create', compact('categories', 'down', 'cid', 'files'));
    }

    /**
     * Публикация загрузки
     */
    public function store(DownStoreRequest $request, Flood $flood, DownService $service): RedirectResponse
    {
        $down = $service->create(getUser(), $request, $request->file('files', []));

        $flood->saveState();

        return redirect()->route('downs.view', ['id' => $down->id])
            ->with('success', __('load::loads.down_added_success'));
    }

    /**
     * Редактирование загрузки
     */
    public function edit(int $id, Request $request): View
    {
        $cid = int($request->input('category'));
        $down = $this->ownDown($id);

        $files = File::query()
            ->where('relate_type', Down::$morphName)
            ->where('relate_id', $down->id)
            ->ordered()
            ->get();

        $categories = $down->category->getChildren();

        return view('load::downs/edit', compact('categories', 'down', 'cid', 'files'));
    }

    /**
     * Сохранение загрузки
     */
    public function update(int $id, OwnDownRequest $request): RedirectResponse
    {
        $down = $this->ownDown($id);

        $down->update([
            'category_id' => $request->category()->id,
            'title'       => $request->string('title')->value(),
            'text'        => $request->string('text')->value(),
            'links'       => $request->links(),
        ]);

        clearCache(['statLoads', 'recentDowns']);

        return redirect()->route('downs.view', ['id' => $down->id])
            ->with('success', __('load::loads.down_edited_success'));
    }

    /**
     * Скачивание файла
     */
    public function download(int $id, int $fid): Response
    {
        $file = File::query()->where('relate_type', Down::$morphName)->find($fid);
        $down = $file?->relate;

        if (! $file || ! $down instanceof Down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if (! (getUser() || setting('down_guest_download'))) {
            abort(403, __('load::loads.download_authorized'));
        }

        if (! $down->active && ! isAdmin(User::ADMIN)) {
            abort(200, __('load::loads.down_not_verified'));
        }

        if (! file_exists(public_path($file->path))) {
            return redirect()->route('downs.view', ['id' => $down->id])
                ->withErrors(__('load::loads.down_not_exist'));
        }

        Reader::countingStat($down);

        return response()->download(public_path($file->path), $file->name);
    }

    /**
     * Скачивание файла по ссылке
     */
    public function downloadLink(int $id, int $linkId): Response
    {
        $down = Down::query()->find($id);

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if (! (getUser() || setting('down_guest_download'))) {
            abort(403, __('load::loads.download_authorized'));
        }

        if (! $down->active && ! isAdmin(User::ADMIN)) {
            abort(200, __('load::loads.down_not_verified'));
        }

        if (! isset($down->links[$linkId])) {
            return redirect()->route('downs.view', ['id' => $down->id])
                ->withErrors(__('load::loads.down_not_exist'));
        }

        Reader::countingStat($down);

        return response()->redirectTo($down->links[$linkId]);
    }

    /**
     * Редиректит старые роуты на новый путь (используется только для редиректов в роутах)
     */
    public function redirectZip(int $id, ?int $fid = null): RedirectResponse
    {
        $file = File::query()->where('relate_type', Down::$morphName)->find($id);
        $down = $file?->relate;

        if (! $file || ! $down instanceof Down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if ($fid) {
            return redirect()->to("/downs/{$down->id}/zip/{$id}/{$fid}", 301);
        }

        return redirect()->to("/downs/{$down->id}/zip/{$id}", 301);
    }

    /**
     * Просмотр zip архива
     */
    public function zip(int $id, int $fid): View
    {
        [$down, $file, $archive] = $this->openZipFile($id, $fid);

        // Обход всех записей архива идёт один раз на файл, дальше из кеша
        $flat = ZipMap::entries($file, $archive);

        $archive->close();

        $tree = ZipTree::build($flat);

        $totalCount = $tree->count;
        $totalSize = $tree->size;

        return view('load::downs/zip', compact('down', 'file', 'tree', 'totalCount', 'totalSize'));
    }

    /**
     * Просмотр файла в zip архиве
     */
    public function zipView(int $id, int $fid, int $zid): View|Response
    {
        [$down, $file] = $this->findZipFile($id, $fid);

        // Индексы перебирают боты — известный по карте промах архив не открывает
        if (ZipMap::has($file, $zid) === false) {
            abort(200, __('load::loads.file_not_read'));
        }

        $archive = $this->openArchive($file);

        $content = $archive->getFromIndex($zid);
        $document = $archive->statIndex($zid);

        if ($content === false) {
            abort(200, __('load::loads.file_not_read'));
        }

        $archive->close();

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);

        $isImage = str_starts_with($mime, 'image/');
        $isText = str_starts_with($mime, 'text/') || in_array($mime, [
            'application/json',
            'application/xml',
            'application/javascript',
            'application/x-httpd-php',
            'application/sql',
        ], true);

        if (! $isImage && ! $isText) {
            abort(200, __('load::loads.file_not_read'));
        }

        if ($isImage) {
            // Содержимое архива неизменно: повторный запрос картинки браузер
            // и кеширующий прокси закрывают сами, не доходя до php
            return response($content)
                ->header('Content-Type', $mime)
                ->header('Content-Length', (string) strlen($content))
                ->header('Content-Disposition', 'inline; filename="' . $document['name'] . '"')
                ->setPublic()
                ->setMaxAge(self::ZIP_CACHE_MAX_AGE)
                ->setEtag(md5($file->id . ':' . $zid . ':' . $document['crc']));
        }

        if (! mb_check_encoding($content, 'utf-8')) {
            $content = mb_convert_encoding($content, 'utf-8', 'windows-1251');
        }

        return view('load::downs/zip_view', compact('down', 'file', 'document', 'content'));
    }

    /**
     * Открывает zip-архив и возвращает загрузку, файл и архив
     */
    private function openZipFile(int $id, int $fid): array
    {
        [$down, $file] = $this->findZipFile($id, $fid);

        return [$down, $file, $this->openArchive($file)];
    }

    /**
     * Находит загрузку и её архив, не открывая сам файл
     *
     * @return array{Down, File}
     */
    private function findZipFile(int $id, int $fid): array
    {
        $down = Down::query()->find($id);
        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if (! $down->active && ! isAdmin(User::ADMIN)) {
            abort(200, __('load::loads.down_not_verified'));
        }

        $file = $down->files->firstWhere('id', $fid);
        if (! $file) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if ($file->extension !== 'zip') {
            abort(200, __('load::loads.archive_only_zip'));
        }

        return [$down, $file];
    }

    /**
     * Открывает архив на чтение
     */
    private function openArchive(File $file): ZipArchive
    {
        $archive = new ZipArchive();

        if ($archive->open(public_path($file->path), ZipArchive::RDONLY) !== true) {
            abort(200, __('load::loads.archive_not_open'));
        }

        return $archive;
    }

    /**
     * Своя загрузка, ещё не проверенная модератором
     */
    private function ownDown(int $id): Down
    {
        $down = Down::query()->where('user_id', getUser('id'))->find($id);

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if ($down->active) {
            abort(200, __('load::loads.down_verified'));
        }

        return $down;
    }

    /**
     * Загрузка файлов может быть закрыта настройкой — кроме администрации
     */
    private function checkUploadOpen(): void
    {
        if (! isAdmin() && ! setting('downupload')) {
            abort(200, __('load::loads.down_closed'));
        }
    }
}
