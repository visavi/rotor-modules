<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guestbook\Http\Requests\GuestbookRequest;
use Modules\Guestbook\Http\Requests\GuestbookTextRequest;
use Modules\Guestbook\Http\Resources\GuestbookResource;
use Modules\Guestbook\Models\Guestbook;

class GuestbookApiController extends Controller
{
    use HandlesApiPagination;

    /**
     * Сообщения гостевой
     */
    public function index(Request $request): JsonResource
    {
        $posts = Guestbook::query()
            ->active()
            ->orderBy('created_at', $this->apiOrder($request, 'desc'))
            ->with('user', 'editUser', 'files')
            ->paginate($this->apiPerPage($request));

        return GuestbookResource::collection($posts);
    }

    /**
     * Добавление сообщения
     */
    public function store(GuestbookRequest $request, Flood $flood, FileService $files): JsonResponse
    {
        $user = auth()->user();
        $text = $request->string('text')->value();
        $active = true;
        $guestName = null;

        if ($user) {
            $user->increment('point', setting('guestbook_point'));
            $user->increment('money', setting('guestbook_money'));
        } else {
            // Гостевой текст приходит без разметки, её ставит сам сайт
            $text = '<p>' . nl2br(e($text), false) . '</p>';
            $active = ! setting('guest_moderation');
            $guestName = $request->input('guest_name');
        }

        $post = Guestbook::query()->create([
            'user_id'    => $user->id ?? null,
            'text'       => $text,
            'ip'         => getIp(),
            'brow'       => getBrowser(),
            'guest_name' => $guestName,
            'active'     => $active,
        ]);

        // Файлы можно приложить к запросу или загрузить заранее — с relate_id = 0
        if ($user) {
            $files->attach($post, $request->file('files', []));
        }

        $flood->saveState();

        sendNotify(
            $text,
            route('guestbook.index', absolute: false),
            __('guestbook::guestbook.guestbook', locale: setting('language')),
        );

        $post->load('user', 'files');

        return response()->json([
            'message' => $active ? __('main.message_added_success') : __('main.message_publish_moderation'),
            'post'    => GuestbookResource::make($post),
        ], 201);
    }

    /**
     * Правка своего сообщения
     */
    public function update(int $id, GuestbookTextRequest $request): JsonResponse
    {
        $post = Guestbook::query()->findOrFail($id);
        $this->authorize('update', $post);

        $post->update([
            'text'         => $request->string('text')->value(),
            'edit_user_id' => auth()->id(),
        ]);

        $post->load('user', 'editUser', 'files');

        return response()->json([
            'message' => __('main.message_edited_success'),
            'post'    => GuestbookResource::make($post),
        ]);
    }
}
