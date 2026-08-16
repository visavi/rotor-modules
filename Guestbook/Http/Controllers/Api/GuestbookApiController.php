<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\CaptchaService;
use App\Services\FileService;
use App\Traits\HandlesApiPagination;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
     *
     * Гостю разрешено писать, если это открыто настройкой — тогда нужна капча,
     * а ссылки в тексте не принимаются
     */
    public function store(Request $request, Flood $flood, FileService $files, CaptchaService $captcha): JsonResponse
    {
        $user = getUser();

        if (! $user && ! setting('bookadds')) {
            abort(403, __('main.not_authorized'));
        }

        $rules = [
            'text' => [
                'required',
                'string',
                'min:' . setting('guestbook_text_min'),
                'max:' . setting('guestbook_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
        ];

        if (! $user) {
            $rules['text'][] = static function (string $attribute, mixed $value, Closure $fail) use ($captcha, $request) {
                if (str_contains((string) $value, '//')) {
                    $fail(__('guestbook::guestbook.without_links'));
                }

                if (! $captcha->verify($request)) {
                    $fail(__('validator.captcha'));
                }
            };

            $rules['guest_name'] = ['nullable', 'string', 'min:3', 'max:20'];
        }

        $validated = $request->validate($rules + FileService::rules(Guestbook::$morphName));

        $text = antimat($validated['text']);
        $active = true;
        $guestName = null;

        if ($user) {
            $user->increment('point', setting('guestbook_point'));
            $user->increment('money', setting('guestbook_money'));
        } else {
            // Гостевой текст приходит без разметки, её ставит сам сайт
            $text = '<p>' . nl2br(check($text), false) . '</p>';
            $active = ! setting('guest_moderation');
            $guestName = $validated['guest_name'] ?? null;
        }

        $post = Guestbook::query()->create([
            'user_id'    => $user->id ?? null,
            'text'       => $text,
            'ip'         => getIp(),
            'brow'       => getBrowser(),
            'guest_name' => $guestName,
            'active'     => $active,
        ]);

        // Медиа можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attachUploaded($post, $request->file('files', []));

        if ($user) {
            $files->attachPending($post);
        }

        clearCache('statGuestbook');
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
    public function update(int $id, Request $request): JsonResponse
    {
        $post = Guestbook::query()
            ->where('user_id', getUser('id'))
            ->find($id);

        if (! $post) {
            abort(404, __('main.message_not_found'));
        }

        // Правка открыта первые десять минут, как и на сайте
        if ($post->created_at->lt(now()->subMinutes(10))) {
            abort(422, __('main.editing_impossible'));
        }

        $validated = $request->validate([
            'text' => [
                'required',
                'string',
                'min:' . setting('guestbook_text_min'),
                'max:' . setting('guestbook_text_max'),
            ],
        ]);

        $post->update([
            'text'         => antimat($validated['text']),
            'edit_user_id' => getUser('id'),
        ]);

        $post->load('user', 'editUser', 'files');

        return response()->json([
            'message' => __('main.message_edited_success'),
            'post'    => GuestbookResource::make($post),
        ]);
    }
}
