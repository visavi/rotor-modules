<?php

declare(strict_types=1);

namespace Modules\Load\Services;

use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Modules\Load\Http\Requests\DownRequest;
use Modules\Load\Models\Down;

/**
 * Создание загрузки — общее для сайта и API
 */
class DownService
{
    public function __construct(private readonly FileService $files)
    {
    }

    /**
     * Создаёт загрузку: от администрации она публикуется сразу,
     * от пользователя ждёт проверки, и администрация получает уведомление
     *
     * @param array<int, UploadedFile> $uploaded файлы из запроса
     */
    public function create(User $user, DownRequest $request, array $uploaded = []): Down
    {
        $down = Down::query()->create([
            'category_id' => $request->category()->id,
            'title'       => $request->string('title')->value(),
            'text'        => $request->string('text')->value(),
            'user_id'     => $user->id,
            'active'      => isAdmin(User::ADMIN),
            'links'       => $request->links(),
        ]);

        // Файлы можно приложить к запросу или загрузить заранее — с relate_id = 0
        $this->files->attach($down, $uploaded);

        if ($down->active) {
            $down->category->increment('count_downs');

            // statLoads складывает count_downs разделов: событие модели сработало до счётчика
            clearCache('statLoads');

            return $down;
        }

        $admins = User::query()->whereIn('level', [User::BOSS, User::ADMIN])->get();

        if ($admins->isNotEmpty()) {
            $text = textNotice('down_upload', [
                'url'   => route('admin.downs.edit', ['id' => $down->id], false),
                'title' => $down->title,
            ]);

            foreach ($admins as $admin) {
                $admin->sendMessage($user, $text, false);
            }
        }

        return $down;
    }
}
