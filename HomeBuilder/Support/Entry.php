<?php

declare(strict_types=1);

namespace Modules\HomeBuilder\Support;

use App\Models\User;
use App\Services\FeedService;
use App\Support\Registry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Запись любого раздела ленты с одинаковыми полями: её получают виды блока «Записи»
 *
 * Шаблону не нужно знать, как устроены модели разделов: заголовок, анонс, картинка
 * и автор лежат в одних и тех же полях. Исходная запись — в model
 */
final readonly class Entry
{
    /**
     * Длина анонса в символах
     */
    public const int EXCERPT = 200;

    public function __construct(
        public string $title,
        public ?string $url,
        public ?CarbonInterface $date,
        public string $text,
        public ?string $image,
        public ?User $author,
        public string $section,
        public ?string $sectionUrl,
        public int $comments,
        public int $rating,
        public Model $model,
    ) {
    }

    /**
     * Запись из модели раздела
     *
     * Текст, автор, дата и картинка берутся у носителя контента: у темы форума
     * это последнее сообщение, как в ленте
     */
    public static function from(Model $post): self
    {
        $config = FeedService::typeConfig($post->getMorphClass());
        $source = isset($config['source']) ? ($config['source']($post) ?? $post) : $post;

        $title = isset($config['title']) ? $config['title']($post) : $post->getAttribute('title');
        $author = $source->getRelationValue('user');
        $date = $source->getAttribute('created_at');
        $breadcrumbs = method_exists($post, 'getBreadcrumbs') ? $post->getBreadcrumbs() : [];

        return new self(
            title: is_string($title) && $title !== '' ? $title : self::sectionLabel($post->getMorphClass()),
            url: method_exists($post, 'getViewUrl') ? $post->getViewUrl() : null,
            date: $date instanceof CarbonInterface ? $date : null,
            text: self::excerpt((string) $source->getAttribute('text')),
            image: self::image($source),
            author: $author instanceof User ? $author : null,
            section: self::sectionLabel($post->getMorphClass()),
            sectionUrl: $breadcrumbs[0]['url'] ?? null,
            comments: (int) ($post->getAttribute('count_comments') ?? $post->getAttribute('count_posts') ?? 0),
            rating: (int) ($source->getAttribute('rating') ?? 0),
            model: $post,
        );
    }

    /**
     * Название раздела по типу ленты
     */
    public static function sectionLabel(string $type): string
    {
        return $type === 'comments' ? __('main.comments') : (Registry::label($type) ?? $type);
    }

    /**
     * Анонс: текст без разметки одной строкой
     */
    private static function excerpt(string $html): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $html)))));

        return Str::limit($text, self::EXCERPT);
    }

    /**
     * Первая картинка записи, null — картинок нет
     */
    private static function image(Model $post): ?string
    {
        $image = method_exists($post, 'getOgImage') ? $post->getOgImage() : '';

        return $image !== '' ? $image : null;
    }
}
