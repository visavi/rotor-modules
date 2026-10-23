<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Guestbook\Http\Requests\GuestbookReplyRequest;
use Modules\Guestbook\Http\Requests\GuestbookTextRequest;
use Modules\Guestbook\Models\Guestbook;

class GuestbookController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $posts = Guestbook::query()
            ->orderByDesc('created_at')
            ->with('user', 'editUser', 'files')
            ->paginate(setting('bookpost'));

        $unpublished = Guestbook::query()->active(false)->count();

        return view('guestbook::admin/guestbook/index', compact('posts', 'unpublished'));
    }

    /**
     * Редактирование сообщения
     */
    public function edit(int $id, Request $request): View
    {
        $page = int($request->input('page'));
        $post = $this->findPost($id);

        return view('guestbook::admin/guestbook/edit', compact('post', 'page'));
    }

    /**
     * Сохранение сообщения
     */
    public function update(int $id, GuestbookTextRequest $request): RedirectResponse
    {
        $post = $this->findPost($id);

        $post->update([
            'text'         => $request->string('text')->value(),
            'edit_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.guestbook.index', ['page' => int($request->input('page'))])
            ->with('success', __('main.message_edited_success'));
    }

    /**
     * Ответ на сообщение
     */
    public function reply(int $id, Request $request): View
    {
        $page = int($request->input('page'));
        $post = $this->findPost($id);

        return view('guestbook::admin/guestbook/reply', compact('post', 'page'));
    }

    /**
     * Сохранение ответа
     */
    public function storeReply(int $id, GuestbookReplyRequest $request): RedirectResponse
    {
        $post = $this->findPost($id);

        $post->update([
            'reply' => $request->string('reply')->value(),
        ]);

        return redirect()
            ->route('admin.guestbook.index', ['page' => int($request->input('page'))])
            ->with('success', __('guestbook::guestbook.answer_success_added'));
    }

    /**
     * Удаление сообщений
     */
    public function delete(Request $request): RedirectResponse
    {
        $page = int($request->input('page', 1));
        $del = intar($request->input('chosen'));

        if (! $del) {
            return redirect()
                ->route('admin.guestbook.index', ['page' => $page])
                ->withErrors(__('validator.deletion'));
        }

        Guestbook::query()->whereIn('id', $del)->get()->each->delete();

        clearCache('statGuestbook');

        return redirect()
            ->route('admin.guestbook.index', ['page' => $page])
            ->with('success', __('main.messages_deleted_success'));
    }

    /**
     * Активация сообщений
     */
    public function publish(Request $request): RedirectResponse
    {
        $page = int($request->input('page', 1));
        $active = intar($request->input('chosen'));

        if (! $active) {
            return redirect()
                ->route('admin.guestbook.index', ['page' => $page])
                ->withErrors(__('validator.published'));
        }

        Guestbook::query()->whereIn('id', $active)->get()->each->update(['active' => true]);

        clearCache('statGuestbook');

        return redirect()
            ->route('admin.guestbook.index', ['page' => $page])
            ->with('success', __('main.messages_published_success'));
    }

    /**
     * Очистка сообщений
     */
    public function clear(): RedirectResponse
    {
        // По одной: вместе с сообщением удаляются его файлы
        Guestbook::query()->with('files')->lazyById()->each->delete();

        clearCache('statGuestbook');

        return redirect()
            ->route('admin.guestbook.index')
            ->with('success', __('guestbook::guestbook.messages_success_cleared'));
    }

    /**
     * Сообщение по id
     */
    private function findPost(int $id): Guestbook
    {
        $post = Guestbook::with('user')->findOrFail($id);

        return $post;
    }
}
