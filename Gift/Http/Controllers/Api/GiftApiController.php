<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Traits\HandlesApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gift\Http\Requests\GiftSendRequest;
use Modules\Gift\Http\Resources\GiftResource;
use Modules\Gift\Http\Resources\GiftUserResource;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Modules\Gift\Services\GiftService;

class GiftApiController extends Controller
{
    use HandlesApiPagination;

    /**
     * Каталог подарков
     */
    public function index(Request $request): JsonResponse
    {
        $gifts = Gift::query()
            ->orderBy('price')
            ->paginate($this->apiPerPage($request, (int) setting('gift_per_page')));

        return response()->json([
            'data' => GiftResource::collection($gifts->items()),
            'meta' => [
                'current_page' => $gifts->currentPage(),
                'last_page'    => $gifts->lastPage(),
                'per_page'     => $gifts->perPage(),
                'total'        => $gifts->total(),
                // Через сколько дней подарок исчезнет из профиля
                'days' => (int) setting('gift_days'),
            ],
        ]);
    }

    /**
     * Подарки пользователя
     */
    public function user(string $login): JsonResponse
    {
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        $gifts = GiftsUser::query()
            ->where('user_id', $user->id)
            // Просроченные подарки в профиле не показываются
            ->where('deleted_at', '>', now())
            ->orderByDesc('created_at')
            ->with('gift', 'sendUser')
            ->get();

        return response()->json([
            'data' => GiftUserResource::collection($gifts),
            'user' => AuthorResource::make($user),
        ]);
    }

    /**
     * Отправка подарка
     */
    public function send(GiftSendRequest $request, GiftService $service): JsonResponse
    {
        $user = getUser();

        $service->send($user, $request->gift(), $request->recipients(), $request->string('text')->value());

        return response()->json([
            'message' => __('gift::gifts.gift_sent'),
            'money'   => $user->fresh()->money,
        ], 201);
    }
}
