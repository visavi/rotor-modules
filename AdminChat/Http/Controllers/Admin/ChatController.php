<?php

declare(strict_types=1);

namespace Modules\AdminChat\Http\Controllers\Admin;

use App\Classes\Validator;
use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\AdminChat\Models\Chat;

class ChatController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(Request $request, Validator $validator): View|RedirectResponse
    {
        $user = getUser();

        if ($user->newchat !== statsNewChat()) {
            $user->update([
                'newchat' => statsNewChat(),
            ]);
        }

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator->length($msg, 5, 1500, ['msg' => __('validator.text')]);

            if ($validator->isValid()) {
                $post = Chat::query()->orderByDesc('created_at')->first();

                if ($post
                    && $post->created_at->gt(now()->subMinutes(30))
                    && $user->id === $post->user_id
                    && (Str::length($msg) + Str::length($post->text) <= 1500)
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

                setFlash('success', __('main.message_added_success'));

                return redirect('admin/chats');
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        $posts = Chat::query()
            ->orderByDesc('created_at')
            ->with('user', 'editUser')
            ->paginate(setting('chatpost'));

        return view('admin_chat::admin/chats/index', compact('posts'));
    }

    /**
     * Редактирование сообщения
     */
    public function edit(int $id, Request $request, Validator $validator): View|RedirectResponse
    {
        $page = int($request->input('page', 1));

        if (! $user = getUser()) {
            abort(403);
        }

        $post = Chat::query()->where('user_id', $user->id)->find($id);

        if (! $post) {
            abort(200, __('main.message_deleted'));
        }

        if ($post->created_at->lt(now()->subMinutes(10))) {
            abort(200, __('main.editing_impossible'));
        }

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator->length($msg, 5, 1500, ['msg' => __('validator.text')]);

            if ($validator->isValid()) {
                $post->update([
                    'text'         => $msg,
                    'edit_user_id' => $user->id,
                ]);

                setFlash('success', __('main.message_edited_success'));

                return redirect('admin/chats?page=' . $page);
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('admin_chat::admin/chats/edit', compact('post', 'page'));
    }

    /**
     * Очистка чата
     */
    public function clear(Validator $validator): RedirectResponse
    {
        $validator->true(isAdmin(User::BOSS), __('main.page_only_admins'));

        if ($validator->isValid()) {
            Chat::query()->truncate();

            setFlash('success', __('admin_chat::admin_chat.success_cleared'));
        } else {
            setFlash('danger', $validator->getErrors());
        }

        return redirect()->route('admin.chats.index');
    }
}
