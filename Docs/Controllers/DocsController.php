<?php

declare(strict_types=1);

namespace Modules\Docs\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DocsController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        return view('docs::index');
    }
}
