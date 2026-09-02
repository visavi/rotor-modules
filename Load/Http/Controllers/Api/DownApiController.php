<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Models\User;
use App\Services\FileService;
use App\Traits\HandlesApiComments;
use Closure;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
use Modules\Load\Http\Resources\DownResource;
use Modules\Load\Http\Resources\LoadResource;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

class DownApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Категории загрузок с подкатегориями
     */
    public function categories(): JsonResource
    {
        $categories = Load::query()
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->with('children')
            ->get();

        return LoadResource::collection($categories);
    }

    /**
     * Список загрузок, при указании category_id — загрузки категории
     */
    public function index(Request $request): JsonResource
    {
        $categoryId = $request->integer('category_id');

        // Сортировка та же, что на сайте: date, name, loads, rating, comments
        [, $orderBy] = Down::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $downs = Down::query()
            ->active()
            ->when($categoryId, static fn ($query) => $query->where('category_id', $categoryId))
            ->select('downs.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderBy(...$orderBy)
            ->with('user', 'category.parent', 'files')
            ->paginate($this->apiPerPage($request));

        return DownResource::collection($downs);
    }

    /**
     * Загрузка с комментариями
     */
    public function view(int $id, Request $request): JsonResource
    {
        $down = Down::query()
            ->select('downs.*', 'polls.vote')
            ->where('downs.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'category.parent', 'files')
            ->first();

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        // Непроверенная загрузка доступна только автору и администрации
        if (! $down->active && ! isAdmin(User::ADMIN) && getUser('id') !== $down->user_id) {
            abort(403, __('load::loads.down_not_verified'));
        }

        // Запись — в data, её комментарии страницей — в comments
        return DownResource::make($down)
            ->additional(['comments' => $this->apiCommentsBlock($down, $request)]);
    }

    /**
     * Создание загрузки
     */
    public function store(Request $request, Flood $flood, FileService $files): JsonResponse
    {
        if (! isAdmin() && ! setting('downupload')) {
            abort(422, __('load::loads.down_closed'));
        }

        $user = getUser();

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'min:1'],
            'title'       => [
                'required',
                'string',
                'min:' . setting('down_title_min'),
                'max:' . setting('down_title_max'),
                // Названия загрузок уникальны — по ним ищут дубликаты релизов
                Rule::unique('downs', 'title'),
            ],
            'text' => [
                'required',
                'string',
                'min:' . setting('down_text_min'),
                'max:' . setting('down_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
            'links'   => ['nullable', 'array'],
            'links.*' => ['string', 'min:5', 'max:100', 'url'],
        ] + FileService::rules(Down::$morphName));

        $category = $this->findOpenCategory((int) $validated['category_id']);
        $links = $this->normalizeLinks($validated['links'] ?? []);
        $uploaded = $request->file('files', []);

        $this->checkAttachments($user->id, 0, count($links) + count($uploaded));

        $down = Down::query()->create([
            'category_id' => $category->id,
            'title'       => $validated['title'],
            'text'        => antimat($validated['text']),
            'user_id'     => $user->id,
            // Загрузка проверяется модератором, публикуется сразу только за админом
            'active' => isAdmin(User::ADMIN),
            'links'  => $links ?: null,
        ]);

        // Дистрибутив можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attachUploaded($down, $uploaded);
        $files->attachPending($down);

        if ($down->active) {
            $down->category->increment('count_downs');
            clearCache(['statLoads', 'recentDowns']);
        } else {
            $this->notifyAdmins($down, $user);
        }

        $flood->saveState();

        $down->load('user', 'category', 'files');

        return response()->json([
            'message' => __('load::loads.down_added_success'),
            'down'    => DownResource::make($down),
        ], 201);
    }

    /**
     * Редактирование своей непроверенной загрузки
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $down = Down::query()->where('user_id', getUser('id'))->find($id);

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        // Проверенную загрузку правит только администрация
        if ($down->active) {
            abort(422, __('load::loads.down_verified'));
        }

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'min:1'],
            'title'       => [
                'required',
                'string',
                'min:' . setting('down_title_min'),
                'max:' . setting('down_title_max'),
                Rule::unique('downs', 'title')->ignore($down->id),
            ],
            'text'    => ['required', 'string', 'min:' . setting('down_text_min'), 'max:' . setting('down_text_max')],
            'links'   => ['nullable', 'array'],
            'links.*' => ['string', 'min:' . setting('down_link_min'), 'max:' . setting('down_link_max'), 'url'],
        ]);

        $category = $this->findOpenCategory((int) $validated['category_id']);
        $links = $this->normalizeLinks($validated['links'] ?? []);

        $this->checkAttachments($down->user_id, $down->id, count($links));

        $down->update([
            'category_id' => $category->id,
            'title'       => $validated['title'],
            'text'        => antimat($validated['text']),
            'links'       => setting('down_allow_links') ? ($links ?: null) : null,
        ]);

        clearCache(['statLoads', 'recentDowns']);

        $down->load('user', 'category', 'files');

        return response()->json([
            'message' => __('load::loads.down_edited_success'),
            'down'    => DownResource::make($down),
        ]);
    }

    /**
     * Проверяет, что дистрибутив приложен и лимит вложений не превышен
     */
    private function checkAttachments(int $userId, int $relateId, int $linksCount): void
    {
        $filesCount = File::query()
            ->where('relate_type', Down::$morphName)
            ->where('relate_id', $relateId)
            ->where('user_id', $userId)
            ->count();

        $total = $filesCount + $linksCount;

        if (! $total) {
            abort(422, __('validator.file_upload_one'));
        }

        if ($total > setting('maxfiles')) {
            abort(422, __('validator.files_max', ['max' => setting('maxfiles')]));
        }
    }

    /**
     * Убирает пустые и повторяющиеся ссылки
     */
    private function normalizeLinks(array $links): array
    {
        return array_values(array_unique(array_diff($links, [''])));
    }

    /**
     * Находит открытую категорию загрузок
     */
    private function findOpenCategory(int $id): Load
    {
        $category = Load::query()->find($id);

        if (! $category) {
            abort(422, __('load::loads.load_not_exist'));
        }

        if ($category->closed) {
            abort(422, __('load::loads.load_closed'));
        }

        return $category;
    }

    /**
     * Сообщает администрации о загрузке, ожидающей проверки
     */
    private function notifyAdmins(Down $down, User $user): void
    {
        $admins = User::query()->whereIn('level', [User::BOSS, User::ADMIN])->get();

        if ($admins->isEmpty()) {
            return;
        }

        $text = textNotice('down_upload', [
            'url'   => route('admin.downs.edit', ['id' => $down->id], false),
            'title' => $down->title,
        ]);

        foreach ($admins as $admin) {
            $admin->sendMessage($user, $text, false);
        }
    }
}
