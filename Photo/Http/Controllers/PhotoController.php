<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\File;
use App\Models\Flood;
use App\Models\User;
use App\Services\FileService;
use App\Traits\HandlesComments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Photo\Http\Requests\PhotoRequest;
use Modules\Photo\Http\Requests\PhotoStoreRequest;
use Modules\Photo\Models\Photo;

class PhotoController extends Controller
{
    use HandlesComments;

    /**
     * Модель для комментариев
     */
    protected string $commentableModelClass = Photo::class;

    /**
     * Главная страница
     */
    public function index(Request $request): View
    {
        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Photo::getSorting($sort, $order);

        $photos = Photo::query()
            ->withUserVote()
            ->orderBy(...$orderBy)
            ->with('user', 'files')
            ->paginate(setting('fotolist'))
            ->appends(compact('sort', 'order'));

        return view('photo::photos/index', compact('photos', 'sorting'));
    }

    /**
     * Просмотр полной фотографии
     */
    public function view(int $id): View
    {
        $photo = Photo::query()
            ->withUserVote()
            ->where('photos.id', $id)
            ->with('user')
            ->firstOrFail();

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($photo);

        return view('photo::photos/view', compact('photo', 'comments', 'files'));
    }

    /**
     * Форма загрузки фото
     */
    public function create(): View
    {
        $this->authorize('create', Photo::class);

        $files = File::query()
            ->pending(Photo::$morphName, auth()->id())
            ->ordered()
            ->get();

        return view('photo::photos/create', compact('files'));
    }

    /**
     * Загрузка фото
     */
    public function store(PhotoStoreRequest $request, Flood $flood, FileService $files): RedirectResponse
    {
        $photo = Photo::query()->create($request->photoFields() + [
            'user_id' => auth()->id(),
        ]);

        $files->attach($photo, $request->file('files', []));

        $flood->saveState();

        return redirect()->route('photos.view', ['id' => $photo->id])
            ->with('success', __('photo::photos.photo_success_uploaded'));
    }

    /**
     * Редактирование фото
     */
    public function edit(int $id, Request $request): View
    {
        $page = int($request->input('page', 1));
        $photo = Photo::query()->findOrFail($id);
        $this->authorize('update', $photo);

        $checked = $photo->closed ? ' checked' : '';

        return view('photo::photos/edit', compact('photo', 'checked', 'page'));
    }

    /**
     * Сохранение фото
     */
    public function update(int $id, PhotoRequest $request): RedirectResponse
    {
        $photo = Photo::query()->findOrFail($id);
        $this->authorize('update', $photo);

        $photo->update($request->photoFields());

        return redirect()->route('photos.user-albums', ['user' => auth()->user()?->login, 'page' => int($request->input('page', 1))])
            ->with('success', __('photo::photos.photo_success_edited'));
    }

    /**
     * Удаление фотографий
     */
    public function delete(int $id, Request $request): RedirectResponse
    {
        $photo = Photo::query()->findOrFail($id);
        $this->authorize('update', $photo);

        $redirect = redirect()->route('photos.user-albums', ['user' => auth()->user()?->login, 'page' => int($request->input('page', 1))]);

        if ($photo->count_comments) {
            return $redirect->withErrors(__('photo::photos.photo_has_comments'));
        }

        $photo->delete();

        return $redirect->with('success', __('photo::photos.photo_success_deleted'));
    }

    /**
     * Альбомы пользователей
     */
    public function albums(): View
    {
        $albums = Photo::query()
            ->select('user_id', 'login')
            ->selectRaw('count(*) as cnt, sum(count_comments) as count_comments')
            ->join('users', 'photos.user_id', 'users.id')
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->with('user')
            ->paginate(setting('photogroup'));

        return view('photo::photos/albums', compact('albums'));
    }

    /**
     * Альбом пользователя
     */
    public function album(Request $request): View
    {
        $login = $request->input('user', auth()->user()?->login);
        $user = User::findByLoginOrFail($login);

        $photos = Photo::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->with('user', 'files')
            ->paginate(setting('fotolist'))
            ->appends(['user' => $user->login]);

        $moder = auth()->id() === $user->id ? 1 : 0;

        return view('photo::photos/user_albums', compact('photos', 'moder', 'user'));
    }

    /**
     * Выводит все комментарии
     */
    public function allComments(): View
    {
        $comments = Comment::query()
            ->select('comments.*', 'title')
            ->where('relate_type', Photo::$morphName)
            ->leftJoin('photos', 'comments.relate_id', 'photos.id')
            ->orderByDesc('comments.created_at')
            ->with('user', 'relate')
            ->capped()
            ->paginate(setting('comments_per_page'));

        return view('photo::photos/all_comments', compact('comments'));
    }

    /**
     * Выводит комментарии пользователя
     */
    public function userComments(Request $request): View
    {
        $login = $request->input('user', auth()->user()?->login);
        $user = User::findByLoginOrFail($login);

        $comments = Comment::query()
            ->select('comments.*', 'title')
            ->where('relate_type', Photo::$morphName)
            ->where('comments.user_id', $user->id)
            ->leftJoin('photos', 'comments.relate_id', 'photos.id')
            ->orderByDesc('comments.created_at')
            ->with('user', 'relate')
            ->paginate(setting('comments_per_page'))
            ->appends(['user' => $user->login]);

        return view('photo::photos/user_comments', compact('comments', 'user'));
    }
}
