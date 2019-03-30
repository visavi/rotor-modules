<?php

declare(strict_types=1);

namespace App\Modules\Gift\Controllers;

use App\Classes\Validator;
use App\Controllers\Admin\AdminController;
use App\Models\User;
use App\Modules\Gift\Models\Gift;
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
            abort(403, 'Доступ запрещен!');
        }
    }

    /**
     * Главная страница
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
                ->notEmpty($gifts, 'Ошибка! Не переданы цены на подарки!');

            if ($validator->isValid()) {

                foreach ($gifts as $id => $price) {
                    Gift::query()->where('id', $id)->update(['price' => $price]);
                }

                setFlash('success', 'Цены успешно сохранены!');
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

    public function user(Request $request, Validator $validator): string
    {
        return '';
    }
}
