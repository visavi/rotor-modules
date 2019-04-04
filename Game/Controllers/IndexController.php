<?php

declare(strict_types=1);

namespace Modules\Game\Controllers;

use App\Controllers\ModuleController;

class IndexController extends ModuleController
{
    /**
     * Главная страница
     */
    public function index(): string
    {
        return view('Game::index');
    }
}
