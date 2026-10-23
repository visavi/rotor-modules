<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Models\User;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Photo\Http\Requests\PhotoRequest;
use Modules\Photo\Http\Requests\PhotoStoreRequest;
use Modules\Photo\Http\Resources\PhotoResource;
use Modules\Photo\Models\Photo;

class PhotoApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Список фото
     */
    public function index(Request $request): JsonResource
    {
        // Сортировка та же, что на сайте: date, rating, comments, name
        [, $orderBy] = Photo::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));
        $user = $this->apiUser($request);

        $photos = Photo::query()
            ->when($user, static fn ($query, User $user) => $query->where('photos.user_id', $user->id))
            ->withUserVote()
            ->orderBy(...$orderBy)
            ->with('user', 'files')
            ->paginate($this->apiPerPage($request));

        return PhotoResource::collection($photos);
    }

    /**
     * Фото с комментариями
     */
    public function view(int $id, Request $request): JsonResource
    {
        $photo = Photo::query()
            ->withUserVote()
            ->where('photos.id', $id)
            ->with('user', 'files')
            ->firstOrFail();

        // Запись — в data, её комментарии страницей — в comments
        return PhotoResource::make($photo)
            ->additional(['comments' => $this->apiCommentsBlock($photo, $request)]);
    }

    /**
     * Загрузка фотографии
     */
    public function store(PhotoStoreRequest $request, Flood $flood, FileService $files): JsonResponse
    {
        if (! isAdmin() && ! setting('photos_create')) {
            abort(422, __('photo::photos.photos_closed'));
        }

        $photo = Photo::query()->create($request->photoFields() + [
            'user_id' => auth()->id(),
        ]);

        // Снимок можно приложить к запросу или загрузить заранее через POST /api/files
        $files->attach($photo, $request->file('files', []));

        clearCache(['statPhotos', 'recentPhotos']);
        $flood->saveState();

        $photo->load('user', 'files');

        return response()->json([
            'message' => __('photo::photos.photo_success_uploaded'),
            'photo'   => PhotoResource::make($photo),
        ], 201);
    }

    /**
     * Редактирование своей фотографии
     */
    public function update(int $id, PhotoRequest $request): JsonResponse
    {
        $photo = $this->findOwnPhoto($id);

        $photo->update($request->photoFields());

        $photo->load('user', 'files');

        return response()->json([
            'message' => __('photo::photos.photo_success_edited'),
            'photo'   => PhotoResource::make($photo),
        ]);
    }

    /**
     * Удаление своей фотографии
     */
    public function destroy(int $id): JsonResponse
    {
        $photo = $this->findOwnPhoto($id);

        // Обсуждаемую запись не удаляют — то же правило, что на сайте
        if ($photo->count_comments) {
            abort(422, __('photo::photos.photo_has_comments'));
        }

        $photo->delete();

        clearCache(['statPhotos', 'recentPhotos']);

        return response()->json(['message' => __('photo::photos.photo_success_deleted')]);
    }

    /**
     * Находит свою фотографию
     */
    private function findOwnPhoto(int $id): Photo
    {
        $photo = Photo::query()->where('user_id', auth()->id())->find($id);

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_author'));
        }

        return $photo;
    }
}
