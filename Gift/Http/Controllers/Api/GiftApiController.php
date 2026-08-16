<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Traits\HandlesApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Gift\Http\Resources\GiftResource;
use Modules\Gift\Http\Resources\GiftUserResource;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;

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
            ->paginate($this->apiPerPage($request, (int) Gift::getConfig('per_page')));

        return response()->json([
            'data' => GiftResource::collection($gifts->items()),
            'meta' => [
                'current_page' => $gifts->currentPage(),
                'last_page'    => $gifts->lastPage(),
                'per_page'     => $gifts->perPage(),
                'total'        => $gifts->total(),
                // Через сколько дней подарок исчезнет из профиля
                'days' => (int) Gift::getConfig('gift_days'),
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
    public function send(int $id, Request $request): JsonResponse
    {
        $user = getUser();
        $gift = Gift::query()->find($id);

        if (! $gift) {
            abort(404, __('gift::gifts.gift_not_found'));
        }

        $validated = $request->validate([
            'user' => ['required', 'string'],
            'text' => ['nullable', 'string', 'max:1000'],
        ]);

        $recipient = getUserByLogin($validated['user']);

        if (! $recipient) {
            abort(404, __('validator.user'));
        }

        if ($user->money < $gift->price) {
            abort(422, __('gift::gifts.money_not_enough'));
        }

        // Заодно подчищаем просроченные, как и на сайте
        GiftsUser::query()->where('deleted_at', '<', now())->delete();

        $text = antimat($validated['text'] ?? '');

        DB::transaction(static function () use ($gift, $user, $recipient, $text) {
            $user->decrement('money', $gift->price);

            GiftsUser::query()->create([
                'gift_id'      => $gift->id,
                'user_id'      => $recipient->id,
                'send_user_id' => $user->id,
                'text'         => $text,
                'deleted_at'   => now()->addDays((int) Gift::getConfig('gift_days')),
            ]);

            $recipient->sendMessage(null, 'Пользователь @' . $user->login . ' отправил вам подарок!' . PHP_EOL
                . '[img]' . $gift->path . '[/img] ' . $text . PHP_EOL
                . '[url=/gifts/' . $recipient->login . ']Мои подарки[/url]');
        });

        return response()->json([
            'message' => __('gift::gifts.gift_sent'),
            'money'   => $user->fresh()->money,
        ], 201);
    }
}
