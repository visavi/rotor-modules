<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Traits\HandlesApiComments;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
            ->select('offers.*', 'polls.vote')
            ->where('type', $type)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('offers.id', 'polls.relate_id')
                    ->where('polls.relate_type', Offer::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
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
            ->select('offers.*', 'polls.vote')
            ->where('offers.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('offers.id', 'polls.relate_id')
                    ->where('polls.relate_type', Offer::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'replyUser', 'files')
            ->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        // Комментарии страницей, сама запись — в additional, как в API форума
        return CommentResource::collection($this->apiComments($offer, $request))
            ->additional(['offer' => OfferResource::make($offer)]);
    }
}
