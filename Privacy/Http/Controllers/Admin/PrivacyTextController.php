<?php

declare(strict_types=1);

namespace Modules\Privacy\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Privacy\Http\Requests\PrivacyTextRequest;
use Modules\Privacy\Models\PrivacyText;

class PrivacyTextController extends AdminController
{
    /**
     * Редактор текста и список версий
     */
    public function index(): View
    {
        $current = PrivacyText::current();

        $versions = PrivacyText::query()
            ->withCount('consents')
            ->orderByDesc('id')
            ->paginate(20);

        return view('privacy::admin/index', compact('current', 'versions'));
    }

    /**
     * Сохранение текста: изменённый текст становится новой версией, старые остаются
     */
    public function store(PrivacyTextRequest $request): RedirectResponse
    {
        $current = PrivacyText::current();
        $version = new PrivacyText(['text' => $request->validated('text'), 'created_at' => now()]);

        // Сравнение уже очищенного текста: пересохранение без правок не плодит версий
        if ($version->sameTextAs($current)) {
            return redirect()->route('privacy.admin.index')
                ->with('info', __('privacy::privacy.text_unchanged'));
        }

        $version->save();

        return redirect()->route('privacy.admin.index')
            ->with('success', __('privacy::privacy.version_published', ['id' => $version->id]));
    }

    /**
     * Текст старой версии: на что соглашались пользователи до правки
     */
    public function show(int $id): View
    {
        $version = PrivacyText::query()->withCount('consents')->findOrFail($id);

        return view('privacy::admin/show', compact('version'));
    }
}
