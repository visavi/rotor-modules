<?php

declare(strict_types=1);

namespace Modules\Privacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Privacy\Models\PrivacyText;

class PrivacyController extends Controller
{
    /**
     * Действующая версия текста согласия
     */
    public function index(): View
    {
        $version = PrivacyText::current();

        return view('privacy::index', compact('version'));
    }
}
