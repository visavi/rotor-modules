<?php

declare(strict_types=1);

namespace Modules\Wall\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\Flood;
use Modules\Wall\Models\Wall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WallController extends Controller
{
    public function index(string $login): View
    {
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        $messages = Wall::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->with('user', 'author')
            ->paginate(setting('wallpost'));

        return view('wall::walls/index', compact('messages', 'user'));
    }

    public function create(string $login, Request $request, Validator $validator, Flood $flood): RedirectResponse
    {
        if (! getUser()) {
            abort(403, __('main.not_authorized'));
        }

        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        if ($request->isMethod('post')) {
            $msg = $request->input('msg');

            $validator
                ->length($msg, setting('comment_text_min'), setting('comment_text_max'), ['msg' => __('validator.text')])
                ->false($flood->isFlood(), ['msg' => __('validator.flood', ['sec' => $flood->getPeriod()])]);

            if ($validator->isValid()) {
                Wall::query()->create([
                    'user_id'    => $user->id,
                    'author_id'  => getUser('id'),
                    'text'       => antimat($msg),
                    'created_at' => SITETIME,
                ]);

                $flood->saveState();
                sendNotify($msg, route('walls.index', ['login' => $user->login], absolute: false), __('wall::walls.wall_posts_login', ['login' => $user->getName()], setting('language')));

                setFlash('success', __('main.record_added_success'));
            } else {
                setInput($request->all());
                setFlash('danger', $validator->getErrors());
            }
        }

        return redirect('walls/' . $user->login);
    }

    public function delete(string $login, Request $request, Validator $validator): JsonResponse
    {
        $id = int($request->input('id'));
        $user = getUserByLogin($login);

        if (! $user) {
            abort(404, __('validator.user'));
        }

        $validator
            ->true($request->ajax(), __('validator.not_ajax'))
            ->notEmpty($id, __('validator.deletion'))
            ->notEmpty($user, __('validator.user'))
            ->true(isAdmin() || getUser('id') === $user->id, __('main.deleted_only_admins'));

        if ($validator->isValid()) {
            Wall::query()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->delete();

            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => current($validator->getErrors()),
        ]);
    }
}
