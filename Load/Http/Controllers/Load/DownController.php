<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Load;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Models\Reader;
use App\Models\User;
use App\Traits\CommentableTrait;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Modules\Load\Services\ZipTree;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class DownController extends Controller
{
    use CommentableTrait;

    /**
     * Модель для комментариев
     */
    protected string $commentableModelClass = Down::class;

    /**
     * Просмотр загрузки
     */
    public function view(int $id): View
    {
        $down = Down::query()
            ->select('downs.*', 'polls.vote')
            ->where('downs.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('category.parent')
            ->first();

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if (! isAdmin(User::ADMIN) && (! $down->active && getUser() && getUser('id') !== $down->user_id)) {
            abort(200, __('load::loads.down_not_verified'));
        }

        $allowDownload = getUser() || setting('down_guest_download');

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($down);

        return view('load::downs/down', compact('down', 'allowDownload', 'comments', 'files'));
    }

    /**
     * Создание загрузки
     */
    public function create(Request $request, Validator $validator, Flood $flood): View|RedirectResponse
    {
        $cid = int($request->input('category'));

        if (! isAdmin() && ! setting('downupload')) {
            abort(200, __('load::loads.down_closed'));
        }

        if (! $user = getUser()) {
            abort(403, __('main.not_authorized'));
        }

        $categories = (new Load())->getChildren();

        if ($categories->isEmpty()) {
            abort(200, __('load::loads.empty_loads'));
        }

        $files = File::query()
            ->where('relate_type', Down::$morphName)
            ->where('relate_id', 0)
            ->where('user_id', $user->id)
            ->orderBy('created_at');

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $text = $request->input('text');
            $links = (array) $request->input('links');
            $links = array_unique(array_diff($links, ['']));

            $category = Load::query()->find($cid);

            $validator
                ->length($title, setting('down_title_min'), setting('down_title_max'), ['title' => __('validator.text')])
                ->length($text, setting('down_text_min'), setting('down_text_max'), ['text' => __('validator.text')])
                ->false($flood->isFlood(), ['msg' => __('validator.flood', ['sec' => $flood->getPeriod()])])
                ->notEmpty($category, ['category' => __('load::loads.load_not_exist')]);

            if ($category) {
                $validator->empty($category->closed, ['category' => __('load::loads.load_closed')]);

                $duplicate = Down::query()->where('title', $title)->count();
                $validator->empty($duplicate, ['title' => __('load::loads.down_name_exists')]);
            }

            $validator->notEmpty($files->count() + count($links), ['files' => __('validator.file_upload_one')]);
            $validator->lte($files->count() + count($links), setting('maxfiles'), ['files' => __('validator.files_max', ['max' => setting('maxfiles')])]);

            if ($validator->isValid()) {
                foreach ($links as $link) {
                    $validator->length($link, 5, 100, ['links' => __('validator.text')])
                        ->url($link, ['links' => __('validator.url')]);
                }
            }

            if ($validator->isValid()) {
                $down = Down::query()->create([
                    'category_id' => $category->id,
                    'title'       => $title,
                    'text'        => $text,
                    'user_id'     => $user->id,
                    'created_at'  => SITETIME,
                    'active'      => isAdmin(User::ADMIN),
                    'links'       => $links ? array_values($links) : null,
                ]);

                $files->update(['relate_id' => $down->id]);

                if (isAdmin(User::ADMIN)) {
                    $down->category->increment('count_downs');
                    clearCache(['statLoads', 'recentDowns']);
                } else {
                    $admins = User::query()->whereIn('level', [User::BOSS, User::ADMIN])->get();

                    if ($admins->isNotEmpty()) {
                        $text = textNotice('down_upload', ['url' => route('admin.downs.edit', ['id' => $down->id], false), 'title' => $down->title]);

                        foreach ($admins as $admin) {
                            $admin->sendMessage($user, $text, false);
                        }
                    }
                }

                $flood->saveState();
                setFlash('success', __('load::loads.down_added_success'));

                return redirect()->route('downs.view', ['id' => $down->id]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $down = new Down();
        $files = $files->get();

        return view('load::downs/create', compact('categories', 'down', 'cid', 'files'));
    }

    /**
     * Редактирование загрузки
     */
    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $cid = int($request->input('category'));

        if (! $user = getUser()) {
            abort(403, __('main.not_authorized'));
        }

        $down = Down::query()->where('user_id', $user->id)->find($id);

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if ($down->active) {
            abort(200, __('load::loads.down_verified'));
        }

        $files = File::query()
            ->where('relate_type', Down::$morphName)
            ->where('relate_id', $down->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $text = $request->input('text');
            $links = (array) $request->input('links');
            $links = array_unique(array_diff($links, ['']));

            $category = Load::query()->find($cid);

            $validator
                ->length($title, setting('down_title_min'), setting('down_title_max'), ['title' => __('validator.text')])
                ->length($text, setting('down_text_min'), setting('down_text_max'), ['text' => __('validator.text')])
                ->notEmpty($category, ['category' => __('load::loads.load_not_exist')])
                ->empty($category->closed, ['category' => __('load::loads.load_closed')]);

            $duplicate = Down::query()
                ->where('title', $title)
                ->where('id', '<>', $down->id)
                ->count();

            $validator->empty($duplicate, ['title' => __('load::loads.down_name_exists')]);
            $validator->notEmpty($files->count() + count($links), ['files' => __('validator.file_upload_one')]);
            $validator->lte($files->count() + count($links), setting('maxfiles'), ['files' => __('validator.files_max', ['max' => setting('maxfiles')])]);

            if ($validator->isValid()) {
                foreach ($links as $link) {
                    $validator->length($link, setting('down_link_min'), setting('down_link_max'), ['links' => __('validator.text')])
                        ->url($link, ['links' => __('validator.url')]);
                }
            }

            if ($validator->isValid()) {
                $links = setting('down_allow_links') ? array_values($links) : null;

                $down->update([
                    'category_id' => $category->id,
                    'title'       => $title,
                    'text'        => $text,
                    'links'       => $links,
                ]);

                clearCache(['statLoads', 'recentDowns']);
                setFlash('success', __('load::loads.down_edited_success'));

                return redirect()->route('downs.view', ['id' => $down->id]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $categories = $down->category->getChildren();

        return view('load::downs/edit', compact('categories', 'down', 'cid', 'files'));
    }

    /**
     * Скачивание файла
     */
    public function download(int $id, int $fid, Validator $validator): Response
    {
        $file = File::query()->where('relate_type', Down::$morphName)->find($fid);

        if (! $file || ! $file->relate) {
            abort(404, __('load::loads.down_not_exist'));
        }

        if (! (getUser() || setting('down_guest_download'))) {
            abort(403, __('load::loads.download_authorized'));
        }

        if (! $file->relate->active && ! isAdmin(User::ADMIN)) {
            abort(200, __('load::loads.down_not_verified'));
        }

        $validator->true(file_exists(public_path($file->path)), __('load::loads.down_not_exist'));

        if ($validator->isValid()) {
            Reader::countingStat($file->relate);

            return response()->download(public_path($file->path), $file->name);
        }

        setFlash('danger', $validator->getErrors());

        return redirect()->route('downs.view', ['id' => $file->relate->id]);
    }

    /**
     * Скачивание файла по ссылке
     */
    public function downloadLink(int $id, int $linkId, Validator $validator): Response
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

        $validator->true($down->links[$linkId] ?? false, __('load::loads.down_not_exist'));

        if ($validator->isValid()) {
            Reader::countingStat($down);

            return response()->redirectTo($down->links[$linkId]);
        }

        setFlash('danger', $validator->getErrors());

        return redirect()->route('downs.view', ['id' => $down->id]);
    }

    /**
     * Просмотр zip архива
     */
    public function zip(int $id, int $fid): View
    {
        [$down, $file, $archive] = $this->openZipFile($id, $fid);

        $flat = [];
        for ($i = 0; $i < $archive->count(); $i++) {
            $stat = $archive->statIndex($i);
            $isDir = str_ends_with($stat['name'], '/');

            $flat[] = [
                'index' => $stat['index'],
                'name'  => $stat['name'],
                'size'  => $stat['size'],
                'isDir' => $isDir,
                'ext'   => $isDir ? '' : getExtension($stat['name']),
            ];
        }

        $archive->close();

        $tree = ZipTree::build($flat);

        $totalCount = $tree['__count'];
        $totalSize = $tree['__size'];

        return view('load::downs/zip', compact('down', 'file', 'tree', 'totalCount', 'totalSize'));
    }

    /**
     * Просмотр файла в zip архиве
     */
    public function zipView(int $id, int $fid, int $zid): View|Response
    {
        [$down, $file, $archive] = $this->openZipFile($id, $fid);

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
            return response($content)
                ->header('Content-Type', $mime)
                ->header('Content-Length', strlen($content))
                ->header('Content-Disposition', 'inline; filename="' . $document['name'] . '"');
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

        $archive = new ZipArchive();
        if ($archive->open(public_path($file->path), ZipArchive::RDONLY) !== true) {
            abort(200, __('load::loads.archive_not_open'));
        }

        return [$down, $file, $archive];
    }

    /**
     * RSS комментариев
     */
    public function rss(int $id): Response
    {
        $down = Down::query()->where('id', $id)->with('lastComments')->first();

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        return response()
            ->view('load::downs/rss_comments', compact('down'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
