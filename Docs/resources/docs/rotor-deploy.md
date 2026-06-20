# Деплой

Rotor поддерживает деплой через [Deployer](https://deployer.org/) — настройка находится в файле `deploy.php`.

## Требования

- Deployer установлен (`composer require --dev deployer/deployer`)
- SSH-доступ к серверу
- Git-репозиторий с исходным кодом
- npm и Node.js на сервере

## Настройка deploy.php

```php
// Адрес репозитория
set('repository', 'git@github.com:your/rotor.git');

// Количество хранимых релизов
set('keep_releases', 5);

// Хост
host('production')
    ->setHostname('your-server.com')
    ->set('remote_user', 'www-data')
    ->set('deploy_path', '/var/www/rotor');
```

## Первый деплой

```bash
# Убедитесь что SSH ключ добавлен на сервер
ssh-copy-id www-data@your-server.com

# Запустить деплой
vendor/bin/dep deploy production
```

При первом деплое Deployer создаст структуру:

```
/var/www/rotor/
├── current/        # символическая ссылка на активный релиз
├── releases/       # хранятся последние N релизов
│   ├── 1/
│   ├── 2/
│   └── ...
└── shared/         # общие файлы между релизами
    ├── .env
    └── public/uploads/
```

## Настройка shared файлов

`.env` и `public/uploads` автоматически шарятся между релизами. После первого деплоя создайте `.env`:

```bash
cp /var/www/rotor/current/.env.example /var/www/rotor/shared/.env
nano /var/www/rotor/shared/.env
```

## Что происходит при деплое

1. Клонирует новый релиз из репозитория
2. Запускает `npm ci && npm run build`
3. Выполняет `composer install --no-dev`
4. Запускает миграции `php artisan migrate --force`
5. Синхронизирует модули `php artisan module:sync`
6. Переключает `current` на новый релиз
7. Перезагружает PHP-FPM

## Откат

```bash
vendor/bin/dep rollback production
```

Переключает `current` на предыдущий релиз мгновенно.

## Настройка Nginx для Deployer

Корневую директорию Nginx укажите на `current/public`:

```nginx
root /var/www/rotor/current/public;
```

## Ручной деплой (без Deployer)

```bash
cd /var/www/rotor
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan module:sync
npm ci && npm run build
php artisan optimize
sudo systemctl reload php8.3-fpm
```
