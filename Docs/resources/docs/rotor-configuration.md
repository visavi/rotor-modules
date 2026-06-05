# Настройка

## Файл .env

Основные настройки хранятся в файле `.env` в корне проекта.

### Приложение

```ini
APP_NAME=Rotor
APP_ENV=production       # production | local
APP_KEY=                 # генерируется: php artisan key:generate
APP_DEBUG=false          # false в production!
APP_URL=https://example.com
```

### База данных

```ini
DB_CONNECTION=mysql      # mysql | pgsql | sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rotor
DB_USERNAME=root
DB_PASSWORD=
```

### Почта

```ini
MAIL_MAILER=smtp         # smtp | sendmail | log | array
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Кеширование и производительность

```ini
CACHE_STORE=file         # file | redis | memcached | database
SESSION_DRIVER=file      # file | redis | database | cookie

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

При `APP_ENV=production` автоматически кешируются роуты и конфигурация. Для применения изменений в `.env` нужно очистить кеш:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Или пересоздать:

```bash
php artisan optimize
```

## Настройки сайта (AdminPanel)

Основные настройки сайта (название, описание, тема, лимиты и т.д.) доступны в административной панели по адресу `/admin/settings`.

Настройки хранятся в БД и доступны в коде через хелпер `setting('key')`:

```php
setting('title')       // Название сайта
setting('description') // Описание сайта
setting('email')       // Email администратора
```

## Кеширование настроек

Настройки сайта кешируются автоматически. Для сброса кеша настроек используйте:

```bash
php artisan cache:clear
```

## Загрузка файлов

Размер загружаемых файлов настраивается в AdminPanel → Настройки.

Убедитесь, что `php.ini` разрешает нужный размер:

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

## Очереди

Для фоновых задач (отправка писем, уведомления) настройте очереди:

```ini
QUEUE_CONNECTION=database    # sync | database | redis
```

Запуск воркера:

```bash
php artisan queue:work --daemon
```

## Капча

Движок поддерживает несколько типов капч: встроенная, math, reCAPTCHA v2/v3. Настройка в AdminPanel → Настройки → Безопасность. Ключи reCAPTCHA вводятся там же.

## Миграции

```bash
php artisan migrate:status          # статус миграций
php artisan migrate                 # выполнить миграции
php artisan migrate:rollback        # откат последней миграции
php artisan migrate:rollback --step=3  # откат N миграций
```
