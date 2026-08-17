<?php

declare(strict_types=1);

namespace Modules\Load\Http\Resources;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\Concerns\ResolvesAttachments;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Load\Models\Down;

/** @mixin Down */
class DownResource extends JsonResource
{
    use ResolvesAttachments;

    public function toArray(Request $request): array
    {
        // Гостю скачивание можно запретить настройкой
        $allowDownload = getUser() || setting('down_guest_download');

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'text'        => absolutizeUrls($this->text),
            'url'         => $this->getViewUrl(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'category_id' => $this->category_id,
            // Категория записи, как раздел у темы форума
            'category' => LoadResource::make($this->whenLoaded('category')),
            'rating'   => $this->rating,
            'vote'     => [
                'type'  => Down::$morphName,
                'id'    => $this->id,
                'value' => $this->getAttribute('vote'),
                'own'   => $this->user_id === getUser('id'),
            ],
            // Непроверенная загрузка видна только автору и администрации
            'active'         => (bool) $this->active,
            'downloads'      => $this->loads,
            'comments_count' => $this->count_comments,
            'user'           => AuthorResource::make($this->user),
            'media'          => FileResource::collection($this->resolveMedia($this->resource)),
            // Дистрибутив отдаётся ссылкой на роут скачивания: прямой путь обошёл бы
            // счётчик загрузок и запрет скачивания гостям
            'can_download' => (bool) $allowDownload,
            'files'        => $this->downloadFiles($allowDownload),
            'links'        => $this->downloadLinks($allowDownload),
            'created_at'   => dateFixed($this->created_at, 'c', true),
            'updated_at'   => dateFixed($this->updated_at, 'c', true),
        ];
    }

    /**
     * Файлы дистрибутива
     */
    private function downloadFiles(bool $allowDownload): array
    {
        return $this->resolveFiles($this->resource)
            ->map(fn (File $file) => [
                'id'           => $file->id,
                'name'         => e($file->name),
                'size'         => $file->size,
                'extension'    => $file->extension,
                'mime_type'    => $file->mime_type,
                'download_url' => $allowDownload
                    ? route('downs.download', ['id' => $this->id, 'fid' => $file->id])
                    : null,
                // Содержимое архива можно посмотреть до скачивания, но только авторизованным
                'archive_url' => $file->extension === 'zip' && getUser()
                    ? route('downs.zip', ['id' => $this->id, 'fid' => $file->id])
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Внешние ссылки на скачивание
     */
    private function downloadLinks(bool $allowDownload): array
    {
        $links = [];

        foreach ($this->links ?? [] as $linkId => $link) {
            $links[] = [
                'name'         => basename((string) $link),
                'download_url' => $allowDownload
                    ? route('downs.download-link', ['id' => $this->id, 'lid' => $linkId])
                    : null,
            ];
        }

        return $links;
    }
}
