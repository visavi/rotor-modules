<?php

declare(strict_types=1);

namespace Modules\AdminChat\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\AdminChat\Http\Requests\ChatRequest;
use Modules\AdminChat\Models\Chat;

class ChatController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(#[CurrentUser] User $user): View
    {
        if ($user->newchat !== statsNewChat()) {
            $user->update([
                'newchat' => statsNewChat(),
            ]);
        }

        $posts = Chat::query()
            ->orderByDesc('created_at')
            ->with('user', 'editUser')
            ->paginate(setting('chatpost'));

        return view('admin_chat::admin/chats/index', compact('posts'));
    }

    /**
     * Добавление сообщения
     */
    public function store(ChatRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $msg = $request->string('msg')->value();
        $post = Chat::query()->orderByDesc('created_at')->first();

        // Своё сообщение подряд в течение получаса дописывается к предыдущему
        if ($post
            && $post->created_at->gt(now()->subMinutes(30))
            && $user->id === $post->user_id
            && (Str::length($msg) + Str::length($post->text) <= ChatRequest::MAX_LENGTH)
        ) {
            $post->update([
                'text' => $post->text . PHP_EOL . $msg,
            ]);
        } else {
            Chat::query()->create([
                'user_id' => $user->id,
                'text'    => $msg,
                'ip'      => getIp(),
                'brow'    => getBrowser(),
            ]);
        }

        clearCache('statChat');
        sendNotify($msg, route('admin.chats.index', absolute: false), __('admin_chat::admin_chat.admin_chat', locale: setting('language')));

        return redirect('admin/chats')
            ->with('success', __('main.message_added_success'));
    }

    /**
     * Редактирование сообщения
     */
    public function edit(int $id, Request $request): View
    {
        $post = $this->ownPost($id);
        $page = int($request->input('page', 1));

        return view('admin_chat::admin/chats/edit', compact('post', 'page'));
    }

    /**
     * Сохранение сообщения
     */
    public function update(int $id, ChatRequest $request): RedirectResponse
    {
        $post = $this->ownPost($id);
        $page = int($request->input('page', 1));

        $post->update([
            'text'         => $request->string('msg')->value(),
            'edit_user_id' => auth()->id(),
        ]);

        return redirect('admin/chats?page=' . $page)
            ->with('success', __('main.message_edited_success'));
    }

    /**
     * Очистка чата
     */
    public function clear(): RedirectResponse
    {
        Chat::query()->delete();

        return redirect()->route('admin.chats.index')
            ->with('success', __('admin_chat::admin_chat.success_cleared'));
    }

    /**
     * Своё сообщение, которое ещё можно править
     */
    private function ownPost(int $id): Chat
    {
        $post = Chat::query()->where('user_id', auth()->id())->find($id);

        if (! $post) {
            abort(404, __('main.message_deleted'));
        }

        if ($post->created_at->lt(now()->subMinutes(10))) {
            abort(403, __('main.editing_impossible'));
        }

        return $post;
    }
}
