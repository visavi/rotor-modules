<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Http\Requests\OfferRequest;
use Modules\Offer\Http\Requests\OfferStoreRequest;
use Modules\Offer\Http\Resources\OfferResource;
use Modules\Offer\Models\Offer;

class OfferApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Список предложений или проблем
     */
    public function index(Request $request): JsonResource
    {
        $type = $request->input('type') === Offer::ISSUE ? Offer::ISSUE : Offer::OFFER;

        // Сортировка та же, что на сайте: date, rating, comments
        [, $orderBy] = Offer::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $offers = Offer::query()
            ->withUserVote()
            ->where('type', $type)
            ->orderBy(...$orderBy)
            ->with('user', 'replyUser', 'files')
            ->paginate($this->apiPerPage($request))
            ->appends(['type' => $type]);

        return OfferResource::collection($offers);
    }

    /**
     * Запись с комментариями
     */
    public function view(int $id, Request $request): JsonResource
    {
        $offer = Offer::query()
            ->withUserVote()
            ->where('offers.id', $id)
            ->with('user', 'replyUser', 'files')
            ->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        // Запись — в data, её комментарии страницей — в comments
        return OfferResource::make($offer)
            ->additional(['comments' => $this->apiCommentsBlock($offer, $request)]);
    }

    /**
     * Создание записи
     */
    public function store(OfferStoreRequest $request, Flood $flood, FileService $files): JsonResponse
    {
        $offer = Offer::query()->create([
            'type'    => $request->input('type'),
            'title'   => $request->string('title')->value(),
            'text'    => $request->string('text')->value(),
            'user_id' => getUser('id'),
            'rating'  => 1,
            'status'  => Offer::WAIT,
        ]);

        // Медиа можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attach($offer, $request->file('files', []));

        $flood->saveState();

        $offer->load('user', 'files');

        return response()->json([
            'message' => __('main.record_added_success'),
            'offer'   => OfferResource::make($offer),
        ], 201);
    }

    /**
     * Редактирование своей записи
     */
    public function update(int $id, OfferRequest $request): JsonResponse
    {
        $offer = Offer::query()
            ->where('id', $id)
            ->where('user_id', getUser('id'))
            ->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        // Решённую запись не правят — на сайте то же правило
        if (! in_array($offer->status, [Offer::WAIT, Offer::PROCESS], true)) {
            abort(422, __('offer::offers.already_resolved'));
        }

        $offer->update([
            'type'       => $request->input('type'),
            'title'      => $request->string('title')->value(),
            'text'       => $request->string('text')->value(),
            'updated_at' => now(),
        ]);

        $offer->load('user', 'files');

        return response()->json([
            'message' => __('main.record_changed_success'),
            'offer'   => OfferResource::make($offer),
        ]);
    }
}
