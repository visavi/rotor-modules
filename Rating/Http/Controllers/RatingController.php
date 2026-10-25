<?php

declare(strict_types=1);

namespace Modules\Rating\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Rating\Http\Requests\RatingRequest;
use Modules\Rating\Models\Rating;

class RatingController extends Controller
{
    /**
     * Изменение рейтинга
     */
    public function index(string $login, Request $request, #[CurrentUser] User $me): View
    {
        $user = $this->recipient($me, $login);
        $vote = $request->input('vote');

        return view('rating::index', compact('user', 'vote'));
    }

    /**
     * Сохранение голоса
     */
    public function store(string $login, RatingRequest $request, #[CurrentUser] User $me): RedirectResponse
    {
        $user = $this->recipient($me, $login);
        $vote = $request->input('vote');
        $text = $request->string('text')->value();

        Rating::query()->create([
            'user_id'      => $me->id,
            'recipient_id' => $user->id,
            'text'         => $text,
            'vote'         => $vote === 'plus' ? '+' : '-',
        ]);

        $user->increment($vote === 'plus' ? 'posrating' : 'negrating');
        $user->update(['rating' => $user->posrating - $user->negrating]);

        $message = textNotice('rating', ['login' => $me->login, 'rating' => $user->rating, 'comment' => $text, 'vote' => __('main.' . $vote)]);
        $user->sendMessage(null, $message);

        return redirect('users/' . $user->login)
            ->with('success', __('rating::ratings.reputation_success_changed'));
    }

    /**
     *  Полученные голоса
     */
    public function received(string $login): View
    {
        $user = User::findByLoginOrFail($login);

        $ratings = Rating::query()
            ->where('recipient_id', $user->id)
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('ratinglist'));

        return view('rating::rathistory', compact('ratings', 'user'));
    }

    /**
     *  Отданные голоса
     */
    public function gave(string $login): View
    {
        $user = User::findByLoginOrFail($login);

        $ratings = Rating::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->with('recipient')
            ->paginate(setting('ratinglist'));

        return view('rating::rathistory_gave', compact('ratings', 'user'));
    }

    /**
     *  Удаление истории
     */
    public function delete(Request $request): JsonResponse
    {
        if (! $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => __('validator.not_ajax'),
            ], 400);
        }

        if (! isAdmin(User::ADMIN)) {
            return response()->json([
                'success' => false,
                'message' => __('main.page_only_admins'),
            ], 403);
        }

        $id = $request->integer('id');

        if (! $id) {
            return response()->json([
                'success' => false,
                'message' => __('validator.deletion'),
            ], 422);
        }

        Rating::query()->find($id)?->delete();

        return response()->json([
            'success' => true,
            'message' => __('main.record_deleted_success'),
        ]);
    }

    /**
     * Пользователь, за которого голосуют, если голос сейчас возможен
     */
    private function recipient(User $me, string $login): User
    {
        $user = User::findByLoginOrFail($login);

        if ($me->id === $user->id) {
            abort(403, __('rating::ratings.reputation_yourself'));
        }

        if ($me->point < setting('editratingpoint')) {
            abort(403, __('rating::ratings.reputation_point', ['point' => plural(setting('editratingpoint'), setting('scorename'))]));
        }

        // Голосовать за того же пользователя можно через 90 дней
        $voted = Rating::query()
            ->where('user_id', $me->id)
            ->where('recipient_id', $user->id)
            ->where('created_at', '>', now()->subMonths(3))
            ->exists();

        if ($voted) {
            abort(403, __('rating::ratings.reputation_already_changed'));
        }

        return $user;
    }
}
