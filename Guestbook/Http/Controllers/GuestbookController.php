<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Services\FileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;
use Modules\Guestbook\Http\Requests\GuestbookRequest;
use Modules\Guestbook\Http\Requests\GuestbookTextRequest;
use Modules\Guestbook\Models\Guestbook;

class GuestbookController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $posts = Guestbook::query()
            ->active()
            ->orderByDesc('created_at')
            ->with('user', 'editUser', 'files')
            ->paginate(10);

        $unpublished = Guestbook::query()->active(false)->count();

        $files = collect();
        if ($user = getUser()) {
            $files = File::query()
                ->pending(Guestbook::$morphName, $user->id)
                ->ordered()
                ->get();
        }

        return view('guestbook::guestbook/index', compact('posts', 'unpublished', 'files'));
    }

    /**
     * Добавление сообщения
     */
    public function add(GuestbookRequest $request, Flood $flood, FileService $files): RedirectResponse
    {
        $msg = $request->string('text')->value();
        $user = getUser();
        $active = ! setting('guest_moderation');
        $guestName = $request->input('guest_name');

        if ($user) {
            $active = true;
            $guestName = null;

            $user->increment('point', setting('guestbook_point'));
            $user->increment('money', setting('guestbook_money'));
        } else {
            // Гостевой текст приходит без разметки, её ставит сам сайт
            $msg = '<p>' . nl2br(e($msg), false) . '</p>';
        }

        $guestbook = Guestbook::query()->create([
            'user_id'    => $user->id ?? null,
            'text'       => $msg,
            'ip'         => getIp(),
            'brow'       => getBrowser(),
            'guest_name' => $guestName,
            'active'     => $active,
        ]);

        if ($user) {
            $files->attach($guestbook, $request->file('files', []));
        } else {
            Cookie::queue('guest_name', $guestName, 525600);
        }

        clearCache('statGuestbook');
        $flood->saveState();

        sendNotify($msg, route('guestbook.index', absolute: false), __('guestbook::guestbook.guestbook', locale: setting('language')));

        return redirect()
            ->route('guestbook.index')
            ->withFragment('post_' . $guestbook->id)
            ->with('success', $active ? __('main.message_added_success') : __('main.message_publish_moderation'));
    }

    /**
     * Редактирование сообщения
     */
    public function edit(int $id): View
    {
        $post = $this->ownPost($id);

        return view('guestbook::guestbook/edit', compact('post'));
    }

    /**
     * Сохранение сообщения
     */
    public function update(int $id, GuestbookTextRequest $request): RedirectResponse
    {
        $post = $this->ownPost($id);

        $post->update([
            'text'         => $request->string('text')->value(),
            'edit_user_id' => getUser('id'),
        ]);

        return redirect()
            ->route('guestbook.index')
            ->withFragment('post_' . $post->id)
            ->with('success', __('main.message_edited_success'));
    }

    /**
     * Своё сообщение, которое ещё можно править
     */
    private function ownPost(int $id): Guestbook
    {
        $post = Guestbook::query()->where('user_id', getUser('id'))->find($id);

        if (! $post) {
            abort(404, __('main.message_not_found'));
        }

        if ($post->created_at->lt(now()->subMinutes(10))) {
            abort(200, __('main.editing_impossible'));
        }

        return $post;
    }
}
