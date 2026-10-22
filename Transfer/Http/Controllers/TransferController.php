<?php

declare(strict_types=1);

namespace Modules\Transfer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Transfer\Http\Requests\TransferRequest;
use Modules\Transfer\Services\TransferService;

class TransferController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->middleware('check.user');
    }

    /**
     * Главная страница
     */
    public function index(Request $request): View
    {
        return view('transfer::transfers/index', ['user' => getUserByLogin($request->input('user'))]);
    }

    /**
     * Перевод денег
     */
    public function send(TransferRequest $request, TransferService $transfers): RedirectResponse
    {
        $transfers->send(
            getUser(),
            $request->recipient(),
            $request->integer('money'),
            $request->string('text')->value() ?: __('main.not_specified'),
        );

        return redirect('transfers')
            ->with('success', __('transfer::transfers.transfer_success_completed'));
    }
}
