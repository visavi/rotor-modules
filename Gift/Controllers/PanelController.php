<?php

declare(strict_types=1);

namespace Modules\Gift\Controllers;

use App\Classes\Validator;
use App\Controllers\Admin\AdminController;
use App\Models\User;
use Exception;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Illuminate\Http\Request;

class PanelController extends AdminController
{
    /**
     * PanelController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (! isAdmin(User::BOSS)) {
            abort(403, __('errors.forbidden'));
        }
    }

    /**
     * Main page
     *
     * @param Request   $request
     * @param Validator $validator
     * @return string
     */
    public function index(Request $request, Validator $validator): string
    {
        if ($request->isMethod('post')) {

            $gifts = intar($request->input('gifts'));

            $validator->equal($request->input('token'), $_SESSION['token'], ['msg' => __('validator.token')])
                ->notEmpty($gifts, __('Gift::gifts.prices_not_transferred'));

            if ($validator->isValid()) {

                foreach ($gifts as $id => $price) {
                    Gift::query()->where('id', $id)->update(['price' => $price]);
                }

                setFlash('success', __('Gift::gifts.prices_saved'));
                redirect('/admin/gifts');
            } else {
                setInput($request->all());
                setFlash('danger', $validator->getErrors());
            }
        }

        $gifts = Gift::query()
            ->orderBy('price')
            ->paginate(50);

        return view('Gift::panel_index', compact('gifts'));
    }

    /**
     * Removes gifts
     *
     * @param Request   $request
     * @param Validator $validator
     * @return void
     * @throws Exception
     */
    public function delete(Request $request, Validator $validator): void
    {
        $id    = int($request->input('id'));
        $login = $request->input('user');

        $validator->equal($request->input('token'), $_SESSION['token'], __('validator.token'));

        $gift = GiftsUser::query()->find($id);
        $validator->notEmpty($gift, __('Gift::gifts.gift_not_found'));

        if ($validator->isValid()) {

            $gift->delete();

            setFlash('success', __('Gift::gifts.gift_deleted'));
        } else {
            setFlash('danger', $validator->getErrors());
        }

        redirect('/gifts/' . $login);
    }
}
