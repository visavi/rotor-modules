# Artisan-команды

## Установка и обслуживание

```bash
php artisan app:permission
```
Устанавливает права на запись для директорий `storage`, `bootstrap/cache`, `public/uploads`, `public/assets/modules`.

```bash
php artisan module:sync
```
Синхронизирует активные модули с ядром: создаёт символические ссылки `public/assets/modules/{module}` → `modules/{Module}/resources/assets`, перепубликует файлы из секции `publish` и сбрасывает кеш модулей. Запускается автоматически при деплое и обновлении ядра.

```bash
php artisan module:registry modules --base-url=https://example.com/modules -o registry.json
```
Собирает `registry.json` из локальных модулей (`module.php` + `changelog.md`) для публикации своего реестра. Подробнее — [Модули → Свой реестр](/docs/rotor-modules).

```bash
php artisan search:import
```
Синхронизирует существующие записи БД с поисковым индексом. Запускать после первой установки или при сбое индекса.

```bash
php artisan docs:sync
php artisan docs:sync --branch=11.x
```
Скачивает Laravel-документацию с репозитория `laravelsu/docs` в `storage/docs/`. По умолчанию ветка `12.x`.

## Очистка данных

```bash
php artisan delete:files    # удаляет прикреплённые файлы без владельца
php artisan delete:logs     # удаляет устаревшие записи логов
php artisan delete:pending  # удаляет пользователей со статусом «ожидающий»
php artisan delete:polls    # удаляет устаревшие опросы
php artisan delete:readers  # удаляет устаревшие записи читателей
php artisan delete:dialogues # удаляет старые диалоги
```

## Уведомления и рассылки

```bash
php artisan add:subscribers  # добавляет подписчиков на рассылку
php artisan add:birthdays    # отправляет поздравления с днём рождения
php artisan message:send     # отправляет отложенные сообщения из очереди
```

## Вспомогательные

```bash
php artisan lang:compare ru en   # сравнивает два языковых файла, выводит отличия
```

## Расписание (Cron)

Следующие команды запускаются автоматически по расписанию — достаточно добавить одну запись в crontab:

```
* * * * * php /path-to-site/artisan schedule:run >> /dev/null 2>&1
```

| Команда | Расписание |
|---------|-----------|
| `delete:files` | ежедневно |
| `delete:logs` | ежедневно |
| `delete:pending` | ежедневно |
| `delete:dialogues` | ежедневно |
| `delete:polls` | еженедельно |
| `delete:readers` | еженедельно |
| `add:subscribers` | каждый час |
| `add:birthdays` | ежедневно в 07:00 |
| `message:send` | каждую минуту |

## Команды модулей

Каждый модуль может добавлять собственные команды. Файлы из `modules/{Module}/Console/` регистрируются автоматически.
