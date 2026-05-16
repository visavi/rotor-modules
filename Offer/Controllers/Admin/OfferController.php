<?php

declare(strict_types=1);

namespace Modules\Offer\Controllers\Admin;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Offer\Models\Offer;

class OfferController extends Controller
{
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

    public function view(int $id): View
    {
        $offer = Offer::query()->where('offers.id', $id)->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        return view('offer::admin/offers/view', compact('offer'));
    }

    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $offer = Offer::query()->where('id', $id)->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        if ($request->isMethod('post')) {
            $title = $request->input('title');
            $text = $request->input('text');
            $type = $request->input('type');
            $closed = empty($request->input('closed')) ? 0 : 1;

            $validator
                ->length($title, setting('offer_title_min'), setting('offer_title_max'), ['title' => __('validator.text')])
                ->length($text, setting('offer_text_min'), setting('offer_text_max'), ['text' => __('validator.text')])
                ->in($type, Offer::TYPES, ['type' => __('offer::offers.type_invalid')]);

            if ($validator->isValid()) {
                $title = antimat($title);
                $text = antimat($text);

                $offer->update([
                    'type'       => $type,
                    'title'      => $title,
                    'text'       => $text,
                    'closed'     => $closed,
                    'updated_at' => SITETIME,
                ]);

                setFlash('success', __('main.record_changed_success'));

                return redirect()->route('admin.offers.view', ['id' => $offer->id]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('offer::admin/offers/edit', compact('offer'));
    }

    public function reply(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $offer = Offer::query()->where('id', $id)->first();

        if (! $offer) {
            abort(404, __('main.record_not_found'));
        }

        if ($request->isMethod('post')) {
            $reply = $request->input('reply');
            $status = $request->input('status');
            $closed = empty($request->input('closed')) ? 0 : 1;

            $validator
                ->length($reply, setting('offer_reply_min'), setting('offer_reply_max'), ['reply' => __('validator.text')])
                ->in($status, Offer::STATUSES, ['status' => __('offer::offers.status_invalid')]);

            if ($validator->isValid()) {
                $reply = antimat($reply);

                $offer->update([
                    'reply'         => $reply,
                    'reply_user_id' => getUser('id'),
                    'status'        => $status,
                    'closed'        => $closed,
                    'updated_at'    => SITETIME,
                ]);

                $text = textNotice('offer_reply', [
                    'url'    => route('offers.view', ['id' => $offer->id], false),
                    'title'  => $offer->title,
                    'text'   => $offer->reply,
                    'status' => strip_tags($offer->getStatus()->toHtml()),
                ]);

                $offer->user->sendMessage(null, $text);

                setFlash('success', __('offer::offers.answer_success_added'));

                return redirect()->route('admin.offers.view', ['id' => $offer->id]);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $statuses = Offer::STATUSES;

        return view('offer::admin/offers/reply', compact('offer', 'statuses'));
    }

    public function restatement(): RedirectResponse
    {
        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }

        restatement('offers');

        return redirect()
            ->route('admin.offers.index')
            ->with('success', __('main.success_recounted'));
    }

    public function delete(Request $request, Validator $validator): RedirectResponse
    {
        $page = int($request->input('page', 1));
        $del = intar($request->input('del'));
        $type = $request->input('type') === Offer::OFFER ? Offer::OFFER : Offer::ISSUE;

        $validator->true($del, __('validator.deletion'));

        if ($validator->isValid()) {
            $offers = Offer::query()->whereIn('id', $del)->get();

            $offers->each(static function (Offer $offer) {
                $offer->delete();
            });

            setFlash('success', __('main.records_deleted_success'));
        } else {
            setFlash('danger', $validator->getErrors());
        }

        return redirect()->route('admin.offers.index', ['type' => $type, 'page' => $page]);
    }
}
