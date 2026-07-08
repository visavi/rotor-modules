<?php

declare(strict_types=1);

namespace Modules\Logs\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Logs\Models\Log;

class LogController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $logs = Log::query()
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('loglist'));

        return view('logs::admin/index', compact('logs'));
    }

    /**
     * Очистка логов
     */
    public function clear(): RedirectResponse
    {
        Log::query()->truncate();

        setFlash('success', __('logs::logs.success_cleared'));

        return redirect()->route('admin.logs.index');
    }
}
