<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Closure;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
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

        // Запись — в data, её комментарии страницей — в comments
        return OfferResource::make($offer)
            ->additional(['comments' => $this->apiCommentsBlock($offer, $request)]);
    }

    /**
     * Создание записи
     */
    public function store(Request $request, Flood $flood, FileService $files): JsonResponse
    {
        $user = getUser();

        $validated = $request->validate([
            'type'  => ['required', Rule::in(Offer::TYPES)],
            'title' => ['required', 'string', 'min:' . setting('offer_title_min'), 'max:' . setting('offer_title_max')],
            'text'  => [
                'required',
                'string',
                'min:' . setting('offer_text_min'),
                'max:' . setting('offer_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
        ] + FileService::rules(Offer::$morphName));

        // Раздел платный: за создание записи списывается порог баллов, как на сайте
        if ($user->point < setting('addofferspoint')) {
            abort(422, __('offer::offers.condition_add', [
                'point' => plural(setting('addofferspoint'), setting('scorename')),
            ]));
        }

        $offer = Offer::query()->create([
            'type'    => $validated['type'],
            'title'   => antimat($validated['title']),
            'text'    => antimat($validated['text']),
            'user_id' => $user->id,
            'rating'  => 1,
            'status'  => Offer::WAIT,
        ]);

        // Медиа можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attachUploaded($offer, $request->file('files', []));
        $files->attachPending($offer);

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
    public function update(int $id, Request $request): JsonResponse
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

        $validated = $request->validate([
            'type'  => ['required', Rule::in(Offer::TYPES)],
            'title' => ['required', 'string', 'min:' . setting('offer_title_min'), 'max:' . setting('offer_title_max')],
            'text'  => ['required', 'string', 'min:' . setting('offer_text_min'), 'max:' . setting('offer_text_max')],
        ]);

        $offer->update([
            'type'       => $validated['type'],
            'title'      => antimat($validated['title']),
            'text'       => antimat($validated['text']),
            'updated_at' => now(),
        ]);

        $offer->load('user', 'files');

        return response()->json([
            'message' => __('main.record_changed_success'),
            'offer'   => OfferResource::make($offer),
        ]);
    }
}
