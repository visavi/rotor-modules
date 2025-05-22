<?php

declare(strict_types=1);

namespace Modules\Gift\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;

class PanelController extends AdminController
{
    /**
     * PanelController constructor.
     */
    public function __construct()
    {
        $this->middleware('check.admin:boss');
    }

    /**
     * Main page
     */
    public function index(Request $request, Validator $validator): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $gifts = intar($request->input('gifts'));

            $validator->equal($request->input('_token'), csrf_token(), ['msg' => __('validator.token')])
                ->notEmpty($gifts, __('gift::gifts.prices_not_transferred'));

            if ($validator->isValid()) {
                foreach ($gifts as $id => $price) {
                    Gift::query()->where('id', $id)->update(['price' => $price]);
                }

                setFlash('success', __('gift::gifts.prices_saved'));

                return redirect('admin/gifts');
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $gifts = Gift::query()
            ->orderBy('price')
            ->paginate(50);

        return view('gift::panel_index', compact('gifts'));
    }

    /**
     * Removes gifts
     */
    public function delete(Request $request, Validator $validator): RedirectResponse
    {
        $id = int($request->input('id'));
        $login = $request->input('user');

        $validator->equal($request->input('_token'), csrf_token(), __('validator.token'));

        $gift = GiftsUser::query()->find($id);
        $validator->notEmpty($gift, __('gift::gifts.gift_not_found'));

        if ($validator->isValid()) {
            $gift->delete();

            setFlash('success', __('gift::gifts.gift_deleted'));
        } else {
            setFlash('danger', $validator->getErrors());
        }

        return redirect('gifts/' . $login);
    }
}
