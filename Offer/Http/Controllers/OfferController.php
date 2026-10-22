<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesComments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Offer\Http\Requests\OfferRequest;
use Modules\Offer\Http\Requests\OfferStoreRequest;
use Modules\Offer\Models\Offer;

class OfferController extends Controller
{
    use HandlesComments;

    /**
     * Модель для комментариев
     */
    protected string $commentableModelClass = Offer::class;

    /**
     * Главная страница
     */
    public function index(Request $request, string $type = 'offer'): View
    {
        $offerCount = Offer::query()->where('type', Offer::OFFER)->count();
        $issueCount = Offer::query()->where('type', Offer::ISSUE)->count();

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Offer::getSorting($sort, $order);

        $offers = Offer::query()
            ->where('type', $type)
            ->orderBy(...$orderBy)
            ->with('user', 'poll')
            ->paginate(setting('postoffers'))
            ->appends(compact('type', 'sort', 'order'));

        return view('offer::offers/index', compact('offers', 'order', 'type', 'sort', 'sorting', 'offerCount', 'issueCount'));
    }

    /**
     * Просмотр записи
     */
    public function view(int $id): View
    {
        $offer = Offer::query()
            ->withUserVote()
            ->where('offers.id', $id)
            ->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        ['comments' => $comments, 'files' => $files] = $this->getCommentsData($offer);

        return view('offer::offers/view', compact('offer', 'comments', 'files'));
    }

    /**
     * Создание записи
     */
    public function create(Request $request): View
    {
        $type = $request->input('type');

        $files = File::query()
            ->pending(Offer::$morphName, getUser('id'))
            ->ordered()
            ->get();

        return view('offer::offers/create', compact('type', 'files'));
    }

    /**
     * Сохранение записи
     */
    public function store(OfferStoreRequest $request, Flood $flood, FileService $files): RedirectResponse
    {
        $offer = Offer::query()->create([
            'type'    => $request->input('type'),
            'title'   => $request->string('title')->value(),
            'text'    => $request->string('text')->value(),
            'user_id' => getUser('id'),
            'rating'  => 1,
            'status'  => Offer::WAIT,
        ]);

        $files->attach($offer, $request->file('files', []));

        $flood->saveState();

        return redirect()->route('offers.view', ['id' => $offer->id])
            ->with('success', __('main.record_added_success'));
    }

    /**
     * Редактирование записи
     */
    public function edit(int $id): View
    {
        $offer = $this->ownOffer($id);

        return view('offer::offers/edit', compact('offer'));
    }

    /**
     * Сохранение изменений
     */
    public function update(int $id, OfferRequest $request): RedirectResponse
    {
        $offer = $this->ownOffer($id);

        $offer->update([
            'type'       => $request->input('type'),
            'title'      => $request->string('title')->value(),
            'text'       => $request->string('text')->value(),
            'updated_at' => now(),
        ]);

        return redirect()->route('offers.view', ['id' => $offer->id])
            ->with('success', __('main.record_changed_success'));
    }

    /**
     * Своя запись, ещё не решённая
     */
    private function ownOffer(int $id): Offer
    {
        $offer = Offer::query()
            ->where('id', $id)
            ->where('user_id', getUser('id'))
            ->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        if (! in_array($offer->status, [Offer::WAIT, Offer::PROCESS], true)) {
            abort(200, __('offer::offers.already_resolved'));
        }

        return $offer;
    }
}
