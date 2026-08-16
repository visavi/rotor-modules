<?php

declare(strict_types=1);

namespace Modules\Wall\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Flood;
use App\Models\User;
use App\Traits\HandlesApiPagination;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wall\Http\Resources\WallResource;
use Modules\Wall\Models\Wall;

class WallApiController extends Controller
{
    use HandlesApiPagination;

    /**
     * Записи на стене пользователя
     */
    public function index(string $login, Request $request): JsonResource
    {
        $user = $this->findUser($login);

        $messages = Wall::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', $this->apiOrder($request, 'desc'))
            ->with('user', 'author')
            ->paginate($this->apiPerPage($request, (int) setting('wallpost')));

        return WallResource::collection($messages)
            ->additional(['user' => AuthorResource::make($user)]);
    }

    /**
     * Запись на стену пользователя
     */
    public function store(string $login, Request $request, Flood $flood): JsonResponse
    {
        $user = $this->findUser($login);

        // Длина та же, что у комментариев: на сайте стена берёт их настройки
        $validated = $request->validate([
            'text' => [
                'required',
                'string',
                'min:' . setting('comment_text_min'),
                'max:' . setting('comment_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
        ]);

        $message = Wall::query()->create([
            'user_id'   => $user->id,
            'author_id' => getUser('id'),
            'text'      => antimat($validated['text']),
        ]);

        $flood->saveState();

        sendNotify(
            $validated['text'],
            route('walls.index', ['login' => $user->login], absolute: false),
            __('wall::walls.wall_posts_login', ['login' => $user->getName()], setting('language')),
        );

        $message->load('user', 'author');

        return response()->json([
            'message' => __('main.record_added_success'),
            'post'    => WallResource::make($message),
        ], 201);
    }

    /**
     * Удаление записи со стены
     *
     * Чистит стену её хозяин, чужие записи трогает только администрация
     */
    public function destroy(string $login, int $id): JsonResponse
    {
        $user = $this->findUser($login);

        if (! isAdmin() && getUser('id') !== $user->id) {
            abort(403, __('main.deleted_only_admins'));
        }

        $deleted = Wall::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if (! $deleted) {
            abort(404, __('main.record_not_found'));
        }

        return response()->json(['message' => __('main.record_deleted_success')]);
    }

    /**
     * Владелец стены
     */
    private function findUser(string $login): User
    {
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        return $user;
    }
}
