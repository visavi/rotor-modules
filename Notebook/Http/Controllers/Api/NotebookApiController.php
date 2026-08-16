<?php

declare(strict_types=1);

namespace Modules\Notebook\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notebook\Models\Notebook;

/**
 * Личные заметки: одна запись на пользователя
 */
class NotebookApiController extends Controller
{
    /**
     * Своя заметка
     */
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->format($this->note())]);
    }

    /**
     * Сохранение заметки
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['nullable', 'string', 'max:10000'],
        ]);

        $note = $this->note();
        $note->fill(['text' => $validated['text'] ?? ''])->save();

        return response()->json([
            'message' => __('main.record_saved_success'),
            'data'    => $this->format($note),
        ]);
    }

    /**
     * Заметка текущего пользователя, у новичка — пустая заготовка
     */
    private function note(): Notebook
    {
        $userId = getUser('id');

        return Notebook::query()
            ->where('user_id', $userId)
            ->firstOrNew(['user_id' => $userId]);
    }

    private function format(Notebook $note): array
    {
        // У новичка заметки ещё нет — приходит пустая заготовка без дат
        if (! $note->exists) {
            return ['text' => null, 'created_at' => null, 'updated_at' => null];
        }

        return [
            'text'       => $note->text ? absolutizeUrls($note->text) : null,
            'created_at' => dateFixed($note->created_at, 'c', true),
            'updated_at' => dateFixed($note->updated_at, 'c', true),
        ];
    }
}
