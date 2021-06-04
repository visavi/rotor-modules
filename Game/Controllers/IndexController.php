<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IndexController extends Controller
{
    /**
     * Главная страница
     *
     * @return View
     */
    public function index(): View
    {
        return view('Game::index');
    }
}
