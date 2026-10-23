<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Gift\Http\Requests\GiftPricesRequest;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;

class PanelController extends AdminController
{
    /**
     * Main page
     */
    public function index(): View
    {
        $gifts = Gift::query()
            ->orderBy('price')
            ->paginate(50);

        return view('gift::panel_index', compact('gifts'));
    }

    /**
     * Saves gift prices
     */
    public function update(GiftPricesRequest $request): RedirectResponse
    {
        foreach (intar($request->validated('gifts')) ?? [] as $id => $price) {
            Gift::query()->where('id', $id)->update(['price' => $price]);
        }

        return redirect('admin/gifts')
            ->with('success', __('gift::gifts.prices_saved'));
    }

    /**
     * Removes gifts
     */
    public function delete(int $id, Request $request): RedirectResponse
    {
        $login = $request->input('user');
        $gift = GiftsUser::query()->find($id);

        if (! $gift) {
            return redirect('gifts/' . $login)
                ->withErrors(__('gift::gifts.gift_not_found'));
        }

        $gift->delete();

        return redirect('gifts/' . $login)
            ->with('success', __('gift::gifts.gift_deleted'));
    }
}
