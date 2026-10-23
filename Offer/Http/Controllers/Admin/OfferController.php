<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteSelectedRequest;
use App\Support\Restatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Offer\Http\Requests\OfferReplyRequest;
use Modules\Offer\Http\Requests\OfferRequest;
use Modules\Offer\Models\Offer;

class OfferController extends Controller
{
    /**
     * Главная страница
     */
    public function index(Request $request, string $type = Offer::OFFER): View
    {
        $offerCount = Offer::query()->where('type', Offer::OFFER)->count();
        $issueCount = Offer::query()->where('type', Offer::ISSUE)->count();

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Offer::getSorting($sort, $order);

        $offers = Offer::query()
            ->where('type', $type)
            ->orderBy(...$orderBy)
            ->with('user')
            ->paginate(setting('postoffers'))
            ->appends(compact('type', 'sort', 'order'));

        return view('offer::admin/offers/index', compact('offers', 'order', 'type', 'sort', 'sorting', 'offerCount', 'issueCount'));
    }

    /**
     * Просмотр записи
     */
    public function view(int $id): View
    {
        $offer = $this->findOffer($id);

        return view('offer::admin/offers/view', compact('offer'));
    }

    /**
     * Редактирование записи
     */
    public function edit(int $id): View
    {
        $offer = $this->findOffer($id);

        return view('offer::admin/offers/edit', compact('offer'));
    }

    /**
     * Сохранение изменений
     */
    public function update(int $id, OfferRequest $request): RedirectResponse
    {
        $offer = $this->findOffer($id);

        $offer->update([
            'type'       => $request->input('type'),
            'title'      => $request->string('title')->value(),
            'text'       => $request->string('text')->value(),
            'closed'     => $request->boolean('closed'),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.offers.view', ['id' => $offer->id])
            ->with('success', __('main.record_changed_success'));
    }

    /**
     * Ответ на предложение
     */
    public function reply(int $id): View
    {
        $offer = $this->findOffer($id);
        $statuses = Offer::STATUSES;

        return view('offer::admin/offers/reply', compact('offer', 'statuses'));
    }

    /**
     * Сохранение ответа
     */
    public function storeReply(int $id, OfferReplyRequest $request): RedirectResponse
    {
        $offer = $this->findOffer($id);

        $offer->update([
            'reply'         => $request->string('reply')->value(),
            'reply_user_id' => auth()->id(),
            'status'        => $request->input('status'),
            'closed'        => $request->boolean('closed'),
            'updated_at'    => now(),
        ]);

        $text = textNotice('offer_reply', [
            'url'    => route('offers.view', ['id' => $offer->id], false),
            'title'  => $offer->title,
            'text'   => $offer->reply,
            'status' => strip_tags($offer->getStatus()->toHtml()),
        ]);

        if ($offer->user->exists) {
            $offer->user->sendMessage(null, $text);
        }

        return redirect()->route('admin.offers.view', ['id' => $offer->id])
            ->with('success', __('offer::offers.answer_success_added'));
    }

    /**
     * Пересчет комментариев
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run('offers');

        return redirect()
            ->route('admin.offers.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Удаление записей
     */
    public function delete(DeleteSelectedRequest $request): RedirectResponse
    {
        $page = int($request->input('page', 1));
        $type = $request->input('type') === Offer::OFFER ? Offer::OFFER : Offer::ISSUE;

        Offer::query()->whereIn('id', $request->ids())->get()->each->delete();

        return redirect()->route('admin.offers.index', ['type' => $type, 'page' => $page])
            ->with('success', __('main.records_deleted_success'));
    }

    /**
     * Запись по id
     */
    private function findOffer(int $id): Offer
    {
        $offer = Offer::query()->find($id);

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        return $offer;
    }
}
