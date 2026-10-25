<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
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
        foreach (array_map(intval(...), $request->safe()->array('gifts')) as $id => $price) {
            Gift::query()->where('id', $id)->update(['price' => $price]);
        }

        return redirect('admin/gifts')
            ->with('success', __('gift::gifts.prices_saved'));
    }

    /**
     * Removes gifts
     */
    public function delete(int $id): RedirectResponse
    {
        $gift = GiftsUser::query()->with('user')->find($id);

        if (! $gift) {
            return back()->withErrors(__('gift::gifts.gift_not_found'));
        }

        $gift->delete();

        // Возврат к подаркам владельца, а не к логину из запроса: массив в нём ронял удаление
        return redirect('gifts/' . $gift->user->login)
            ->with('success', __('gift::gifts.gift_deleted'));
    }
}
