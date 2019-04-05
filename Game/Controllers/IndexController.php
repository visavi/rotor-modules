<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Controllers\BaseController;

class IndexController extends BaseController
{
    /**
     * Главная страница
     */
    public function index(): string
    {
        return view('Game::index');
    }
}
