<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Closure;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        // Сортировка та же, что на сайте: date, rating, comments
        [, $orderBy] = Photo::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $photos = Photo::query()
            ->select('photos.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('photos.id', 'polls.relate_id')
                    ->where('polls.relate_type', Photo::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
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
            ->select('photos.*', 'polls.vote')
            ->where('photos.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('photos.id', 'polls.relate_id')
                    ->where('polls.relate_type', Photo::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'files')
            ->first();

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_exist'));
        }

        // Запись — в data, её комментарии страницей — в comments
        return PhotoResource::make($photo)
            ->additional(['comments' => $this->apiCommentsBlock($photo, $request)]);
    }

    /**
     * Загрузка фотографии
     */
    public function store(Request $request, Flood $flood, FileService $files): JsonResponse
    {
        if (! isAdmin() && ! setting('photos_create')) {
            abort(422, __('photo::photos.photos_closed'));
        }

        $user = getUser();

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:' . setting('photo_title_min'), 'max:' . setting('photo_title_max')],
            'text'  => [
                'nullable',
                'string',
                'min:' . setting('photo_text_min'),
                'max:' . setting('photo_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
            'closed' => ['nullable', 'boolean'],
        ] + FileService::rules(Photo::$morphName));

        $uploaded = $request->file('files', []);

        // Снимок обязателен: он и есть содержимое записи. Считаем и приложенные
        // к запросу, и загруженные заранее через POST /api/files
        $pending = File::query()
            ->where('relate_type', Photo::$morphName)
            ->where('relate_id', 0)
            ->where('user_id', $user->id)
            ->exists();

        if (! $uploaded && ! $pending) {
            abort(422, __('validator.image_upload_failed'));
        }

        $photo = Photo::query()->create([
            'user_id' => $user->id,
            'title'   => $validated['title'],
            'text'    => antimat((string) ($validated['text'] ?? '')),
            'closed'  => (int) ($validated['closed'] ?? 0),
        ]);

        $files->attachUploaded($photo, $uploaded);
        $files->attachPending($photo);

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
    public function update(int $id, Request $request): JsonResponse
    {
        $photo = $this->findOwnPhoto($id);

        $validated = $request->validate([
            'title'  => ['required', 'string', 'min:' . setting('photo_title_min'), 'max:' . setting('photo_title_max')],
            'text'   => ['nullable', 'string', 'min:' . setting('photo_text_min'), 'max:' . setting('photo_text_max')],
            'closed' => ['nullable', 'boolean'],
        ]);

        $photo->update([
            'title'  => $validated['title'],
            'text'   => antimat((string) ($validated['text'] ?? '')),
            'closed' => (int) ($validated['closed'] ?? 0),
        ]);

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
        $photo = Photo::query()->where('user_id', getUser('id'))->find($id);

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_author'));
        }

        return $photo;
    }
}
