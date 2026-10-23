<?php

declare(strict_types=1);

namespace Modules\Wall\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Wall\Http\Requests\WallRequest;
use Modules\Wall\Models\Wall;

class WallController extends Controller
{
    /**
     * Главная страница
     */
    public function index(string $login): View
    {
        $user = $this->findUser($login);

        $messages = Wall::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->with('user', 'author')
            ->paginate(setting('wallpost'));

        return view('wall::walls/index', compact('messages', 'user'));
    }

    /**
     * Добавление записи на стену
     */
    public function create(string $login, WallRequest $request, Flood $flood): RedirectResponse
    {
        $user = $this->findUser($login);
        $text = $request->string('text')->value();

        Wall::query()->create([
            'user_id'   => $user->id,
            'author_id' => auth()->id(),
            'text'      => $text,
        ]);

        $flood->saveState();
        sendNotify($text, route('walls.index', ['login' => $user->login], absolute: false), __('wall::walls.wall_posts_login', ['login' => $user->getName()], setting('language')));

        return redirect('walls/' . $user->login)
            ->with('success', __('main.record_added_success'));
    }

    /**
     * Удаление записи со стены
     */
    public function delete(string $login, Request $request): JsonResponse
    {
        $user = $this->findUser($login);

        if (! $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => __('validator.not_ajax'),
            ], 400);
        }

        $id = int($request->input('id'));

        if (! $id) {
            return response()->json([
                'success' => false,
                'message' => __('validator.deletion'),
            ], 422);
        }

        $wall = Wall::query()->where('user_id', $user->id)->findOrFail($id);
        $this->authorize('delete', $wall);

        $wall->delete();

        return response()->json([
            'success' => true,
            'message' => __('main.record_deleted_success'),
        ]);
    }

    /**
     * Владелец стены
     */
    private function findUser(string $login): User
    {
        $user = User::findByLoginOrFail($login);

        return $user;
    }
}
