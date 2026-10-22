<?php

declare(strict_types=1);

namespace Modules\Transfer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Transfer\Http\Requests\TransferRequest;
use Modules\Transfer\Http\Resources\TransferResource;
use Modules\Transfer\Models\Transfer;
use Modules\Transfer\Services\TransferService;

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
    public function store(TransferRequest $request, TransferService $transfers): JsonResponse
    {
        $user = getUser();

        $transfers->send(
            $user,
            $request->recipient(),
            $request->integer('money'),
            $request->string('text')->value() ?: __('main.not_specified'),
        );

        return response()->json([
            'message' => __('transfer::transfers.transfer_success_completed'),
            'money'   => $user->fresh()->money,
        ], 201);
    }
}
