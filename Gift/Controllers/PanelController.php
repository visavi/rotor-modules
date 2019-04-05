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
            abort(403, trans('errors.forbidden'));
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
            $token = check($request->input('token'));

            $validator->equal($token, $_SESSION['token'], ['msg' => trans('validator.token')])
                ->notEmpty($gifts, trans('Gift::gifts.prices_not_transferred'));

            if ($validator->isValid()) {

                foreach ($gifts as $id => $price) {
                    Gift::query()->where('id', $id)->update(['price' => $price]);
                }

                setFlash('success', trans('Gift::gifts.prices_saved'));
                redirect('/admin/gifts');
            } else {
                setInput($request->all());
                setFlash('danger', $validator->getErrors());
            }
        }

        $total = Gift::query()->count();
        $page  = paginate(50, $total);

        $gifts = Gift::query()
            ->orderBy('price')
            ->limit($page->limit)
            ->offset($page->offset)
            ->get();

        return view('Gift::panel_index', compact('gifts', 'page'));
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
        $token = check($request->input('token'));
        $id    = int($request->input('id'));
        $login = check($request->input('user'));

        $validator->equal($token, $_SESSION['token'], trans('validator.token'));

        $gift = GiftsUser::query()->find($id);
        $validator->notEmpty($gift, trans('Gift::gifts.gift_not_found'));

        if ($validator->isValid()) {

            $gift->delete();

            setFlash('success', trans('Gift::gifts.gift_deleted'));
        } else {
            setFlash('danger', $validator->getErrors());
        }

        redirect('/gifts/' . $login);
    }
}
