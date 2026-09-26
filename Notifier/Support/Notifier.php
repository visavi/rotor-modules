<?php

declare(strict_types=1);

namespace Modules\Notifier\Support;

use App\Models\User;
use Illuminate\Support\Facades\File;

class Notifier
{
    /**
     * Минимальный и максимальный период опроса в секундах
     */
    public const int MIN_INTERVAL = 10;

    public const int MAX_INTERVAL = 3600;

    /**
     * Период опроса по умолчанию
     */
    public const int DEFAULT_INTERVAL = 60;

    /**
     * Готовые варианты периода опроса для страницы настроек
     */
    public const array INTERVALS = [15, 30, 60, 120, 300, 600];

    /**
     * Расширения, которые считаются звуком
     */
    private const array EXTENSIONS = ['mp3', 'wav', 'ogg', 'oga', 'm4a'];

    /**
     * Найденные звуки, чтобы не сканировать каталог на каждый вызов
     *
     * @var array<string, string>|null
     */
    private static ?array $sounds = null;

    /**
     * Путь к каталогу со звуками
     */
    public static function soundsPath(): string
    {
        return base_path('modules/Notifier/resources/assets/sounds');
    }

    /**
     * Список доступных звуков [файл => название]
     *
     * Каталог сканируется, поэтому администратор может положить туда свой файл
     *
     * @return array<string, string>
     */
    public static function sounds(): array
    {
        if (self::$sounds !== null) {
            return self::$sounds;
        }

        $path = self::soundsPath();

        if (! is_dir($path)) {
            return self::$sounds = [];
        }

        $sounds = [];
        foreach (File::files($path) as $file) {
            if (! in_array(strtolower($file->getExtension()), self::EXTENSIONS, true)) {
                continue;
            }

            $name = $file->getFilename();
            $key = pathinfo($name, PATHINFO_FILENAME);
            $title = __('notifier::notifier.sound_' . $key);

            // Для своих файлов перевода нет — показываем имя как есть
            $sounds[$name] = str_starts_with($title, 'notifier::') ? $key : $title;
        }

        ksort($sounds);

        return self::$sounds = $sounds;
    }

    /**
     * Ссылка на звук или null, если файл не выбран или отсутствует
     */
    public static function soundUrl(?string $sound): ?string
    {
        if (! $sound || ! array_key_exists($sound, self::sounds())) {
            return null;
        }

        return asset('assets/modules/notifiers/sounds/' . $sound);
    }

    /**
     * Период опроса в секундах, приведённый к допустимым границам
     */
    public static function interval(): int
    {
        $interval = (int) setting('notifier_interval');

        if (! $interval) {
            $interval = self::DEFAULT_INTERVAL;
        }

        return max(self::MIN_INTERVAL, min(self::MAX_INTERVAL, $interval));
    }

    /**
     * Данные для клиентского скрипта
     *
     * @return array<string, mixed>
     */
    public static function config(User $user): array
    {
        $config = [
            // Относительные ссылки: сайт может открываться не на том хосте,
            // который прописан в APP_URL, и запрос ушёл бы на чужой origin
            'url'      => route('notifier.check', absolute: false),
            'userId'   => $user->id,
            'count'    => $user->getCountNewMessages(),
            'interval' => self::interval() * 1000,
        ];

        // Дальше только то, что включено: незачем возить по странице настройки
        // выключенных способов уведомления
        $volume = max(0, min(100, (int) setting('notifier_volume')));
        $sound = $volume > 0 ? self::soundUrl(setting('notifier_sound')) : null;

        if ($sound) {
            $config['sound'] = $sound;
            $config['volume'] = $volume / 100;
        }

        if (setting('notifier_title')) {
            $config['title'] = true;
        }

        if (setting('notifier_desktop')) {
            $config['desktop'] = true;
            $config['link'] = route('messages.index', absolute: false);
            $config['texts'] = [
                'title' => __('notifier::notifier.desktop_title'),
                'body'  => __('notifier::notifier.desktop_body'),
            ];
        }

        return $config;
    }

    /**
     * Метка версии файла скрипта для сброса кеша браузера
     */
    public static function assetVersion(): int
    {
        $file = base_path('modules/Notifier/resources/assets/js/notifier.js');

        return is_file($file) ? (int) filemtime($file) : 0;
    }
}
