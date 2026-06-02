# Обновление

## Обновление через Composer

Стандартный способ — обновить пакет до последней стабильной версии:

```bash
composer update visavi/rotor
```

Для обновления до нестабильной (dev) версии:

```bash
composer update visavi/rotor --stability=dev
```

## После обновления

Выполните обязательные шаги:

```bash
# 1. Применить новые миграции
php artisan migrate

# 2. Обновить символические ссылки модулей
php artisan module:link

# 3. Пересобрать CSS и JS
npm ci
npm run build

# 4. Очистить кеш
php artisan optimize:clear
```

## Проверка текущей версии

```bash
php artisan --version
```

Или в коде:

```php
ROTOR_VERSION  // константа с текущей версией
```

## Обновление через Git

Если проект клонирован из репозитория:

```bash
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate
php artisan module:link
npm ci && npm run build
php artisan optimize:clear
```

## Обновление с помощью Deployer

Если настроен Deployer — достаточно одной команды:

```bash
vendor/bin/dep deploy production
```

Deployer автоматически выполнит `composer install`, `npm run build`, `module:link` и переключит релиз без даунтайма.

## Что проверить после обновления

1. Главная страница открывается без ошибок
2. AdminPanel доступна
3. Загрузка файлов работает
4. `php artisan migrate:status` — все миграции применены

## Откат

Deployer хранит последние 5 релизов. Откат к предыдущему:

```bash
vendor/bin/dep rollback production
```

Вручную — откатить последнюю миграцию:

```bash
php artisan migrate:rollback
```
