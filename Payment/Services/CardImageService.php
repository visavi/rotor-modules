<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Modules\Payment\Models\PaidAdvert;

/**
 * Картинка рекламной карточки
 *
 * Одна картинка на два места (лента и сайдбар), поэтому всегда квадрат:
 * покупателю не нужно подгонять пропорции, карточки выглядят одинаково
 */
class CardImageService
{
    private const int SIZE = 600;

    public function __construct(
        private readonly ImageManager $imageManager,
    ) {
        //
    }

    /**
     * Каталог картинок от корня сайта
     */
    public static function path(): string
    {
        return (new PaidAdvert())->uploadPath;
    }

    /**
     * Сохраняет картинку квадратом и возвращает путь от корня сайта
     */
    public function store(UploadedFile $file): string
    {
        File::ensureDirectoryExists(public_path(self::path()));

        $image = $this->imageManager->decode($file);
        $image->coverDown(self::SIZE, self::SIZE);
        $image->encodeUsingFormat(Format::WEBP);

        $path = self::path() . '/' . uniqueName('webp');
        $image->save(public_path($path));

        return $path;
    }

    /**
     * Удаляет картинку из каталога карточек, если на неё больше никто не ссылается
     *
     * Одни и те же данные заказа можно оплатить дважды: у двух карточек одна картинка
     */
    public function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, self::path() . '/')) {
            return;
        }

        if (PaidAdvert::query()->where('image', $path)->exists()) {
            return;
        }

        deleteFile(public_path($path));
    }
}
