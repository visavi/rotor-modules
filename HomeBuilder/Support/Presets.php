<?php

declare(strict_types=1);

namespace Modules\HomeBuilder\Support;

use Modules\HomeBuilder\Models\Block;

/**
 * Готовые наборы блоков для пустой главной
 *
 * Разделы набора берутся из включённых модулей: блок раздела, которого
 * на сайте нет, в набор не попадает
 */
final class Presets
{
    public const array NAMES = ['portal', 'news', 'community'];

    /**
     * Блоки набора в порядке вывода
     *
     * @return list<array<string, mixed>>
     */
    public static function blocks(string $name): array
    {
        $blocks = match ($name) {
            'portal' => [
                self::text(),
                self::entries('news', 'details', 5, 12),
                self::entries('topics', 'list', 8, 6),
                self::entries('photos', 'gallery', 8, 6),
            ],
            'news' => [
                self::entries('news', 'cards', 5, 8),
                self::entries('topics', 'list', 10, 4),
                self::entries('articles', 'details', 5, 12),
            ],
            'community' => [
                self::text(),
                self::entries('topics', 'details', 10, 8),
                self::entries('comments', 'list', 10, 4),
                self::entries('photos', 'gallery', 8, 12),
            ],
            default => [],
        };

        $sources = Block::sourceList();

        return array_values(array_filter(
            $blocks,
            static fn (array $block) => $block['type'] !== Block::ENTRIES || isset($sources[$block['sources'][0]]),
        ));
    }

    /**
     * Приветствие: текст, который владелец заменит своим
     *
     * @return array<string, mixed>
     */
    private static function text(): array
    {
        return [
            'type'  => Block::TEXT,
            'title' => __('home_builder::home_builder.preset_welcome_title', ['site' => setting('title')]),
            'text'  => '<p>' . __('home_builder::home_builder.preset_welcome_text') . '</p>',
        ];
    }

    /**
     * Записи одного раздела, заголовок — название раздела
     *
     * @return array<string, mixed>
     */
    private static function entries(string $source, string $layout, int $items, int $width): array
    {
        return [
            'type'    => Block::ENTRIES,
            'title'   => Entry::sectionLabel($source),
            'sources' => [$source],
            'layout'  => $layout,
            'items'   => $items,
            'width'   => $width,
        ];
    }
}
