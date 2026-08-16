<?php

declare(strict_types=1);

namespace Modules\Transfer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HandlesApiPagination;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Transfer\Http\Resources\TransferResource;
use Modules\Transfer\Models\Transfer;

class TransferApiController extends Controller
{
    use HandlesApiPagination;

    /**
     * Свои переводы: и отправленные, и полученные
     */
    public function index(Request $request): JsonResponse
    {
        $userId = getUser('id');

        $transfers = Transfer::query()
            ->where('user_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->orderBy('created_at', $this->apiOrder($request, 'desc'))
            ->with('user', 'recipientUser')
            ->paginate($this->apiPerPage($request));

        return response()->json([
            'data' => TransferResource::collection($transfers->items()),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page'    => $transfers->lastPage(),
                'per_page'     => $transfers->perPage(),
                'total'        => $transfers->total(),
            ],
        ]);
    }

    /**
     * Перевод денег другому пользователю
     */
    public function store(Request $request): JsonResponse
    {
        $user = getUser();

        $validated = $request->validate([
            'user' => [
                'required',
                'string',
                static function (string $attribute, mixed $value, Closure $fail) use ($user) {
                    $recipient = getUserByLogin($value);

                    if (! $recipient) {
                        $fail(__('validator.user'));
                    } elseif ($recipient->id === $user->id) {
                        $fail(__('transfer::transfers.transfer_yourself'));
                    }
                },
            ],
            'money' => [
                'required',
                'integer',
                'min:1',
                'max:' . $user->money,
            ],
            'text' => ['nullable', 'string', 'max:' . setting('comment_text_max')],
        ], [
            'money.max' => __('transfer::transfers.transfer_not_money'),
            'money.min' => __('transfer::transfers.transfer_wrong_amount'),
        ]);

        // Переводы открыты не всем: нужен порог баллов, как и на сайте
        if ($user->point < setting('sendmoneypoint')) {
            abort(422, __('transfer::transfers.transfer_point', [
                'point' => plural(setting('sendmoneypoint'), setting('scorename')),
            ]));
        }

        /** @var User $recipient */
        $recipient = getUserByLogin($validated['user']);
        $money = (int) $validated['money'];
        $comment = $validated['text'] ?? __('main.not_specified');

        DB::transaction(static function () use ($user, $recipient, $money, $comment) {
            $user->decrement('money', $money);
            $recipient->increment('money', $money);

            $recipient->sendMessage(null, textNotice('transfer', [
                'login'   => $user->login,
                'money'   => plural($money, setting('moneyname')),
                'comment' => $comment,
            ]));

            Transfer::query()->create([
                'user_id'      => $user->id,
                'recipient_id' => $recipient->id,
                'text'         => $comment,
                'total'        => $money,
            ]);
        });

        return response()->json([
            'message' => __('transfer::transfers.transfer_success_completed'),
            'money'   => $user->fresh()->money,
        ], 201);
    }
}
