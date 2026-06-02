# Установка

## Требования

| Компонент | Минимальная версия |
|-----------|-------------------|
| PHP       | 8.3               |
| MySQL     | 5.7.8             |
| MariaDB   | 10.2.7            |
| PostgreSQL | 9.2              |

## Установка одной командой (рекомендуется)

```bash
composer create-project visavi/rotor .
```

Для установки последней (нестабильной) версии:

```bash
composer create-project --stability=dev visavi/rotor .
```

После этого перейдите на главную страницу сайта — вас перекинет на установщик.

## Установка из репозитория (вручную)

### 1. Настройка веб-сервера

Корневой директорией сайта должна быть папка `public`.

- **Apache**: `.htaccess` в корне автоматически перенаправляет запросы в `public`. Работает без дополнительной настройки.
- **Nginx**: см. раздел [Настройка Nginx](#настройка-nginx).

### 2. Распакуйте архив или клонируйте репозиторий

```bash
git clone https://github.com/visavi/rotor.git .
```

### 3. Настройте .env

Переименуйте `.env.example` в `.env` и заполните параметры:

```ini
APP_ENV=production
APP_KEY=             # заполнится автоматически

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rotor
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=admin@example.com
```

### 4. Установите зависимости

```bash
composer install --no-dev --optimize-autoloader
```

### 5. Настройте права доступа

```bash
php artisan app:permission
```

Или вручную — права на запись для:
- `public/uploads`
- `public/assets/modules`
- `bootstrap/cache`
- `storage`

### 6. Создайте базу данных

```sql
CREATE DATABASE rotor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Выполните миграции и заполнение

```bash
php artisan migrate
php artisan db:seed
```

После этого перейдите на главную страницу — запустится веб-установщик.

## Настройка Nginx

В блок `server` добавьте:

```nginx
if (!-d $request_filename) {
    rewrite ^/(.*)/$ /$1 permanent;
}

location ~* /(assets|themes|uploads)/.*\.php$ {
    deny all;
}
```

В `location /` замените строку:

```nginx
# Было:
try_files $uri $uri/ =404;

# Стало:
try_files $uri $uri/ /index.php?$query_string;
```

## Настройка Apache

По умолчанию все файлы размещаются в `public_html`. `.htaccess` в корне перенаправляет запросы в `public`.

Если этот способ не работает — переместите содержимое `public` в `public_html`, затем раскомментируйте код в `app/Providers/AppServiceProvider.php`, указывающий `public_html` вместо `public`.

## Запуск без сервера (для разработки)

```bash
cd public
php -S localhost:8000
# или
php artisan serve
```

## Сборка CSS и JS

```bash
npm ci
npm run build
```

## Настройка Cron

```
* * * * * php /path-to-site/artisan schedule:run >> /dev/null 2>&1
```
