<?php

declare(strict_types=1);

namespace Modules\Template\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Template\Models\Template;

class TemplateController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $templates = Template::query()
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(30);

        return view('template::admin/index', compact('templates'));
    }

    /**
     * Удаление шаблона
     */
    public function delete(Request $request): RedirectResponse
    {
        $id = (int) $request->input('id');

        $template = Template::query()->find($id);
        if ($template) {
            $template->delete();
            Cache::forget('statTemplate');
            setFlash('success', __('template::template.record_deleted'));
        }

        return redirect()->route('admin.template.index');
    }
}
