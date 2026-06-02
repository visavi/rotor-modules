# Роли и права доступа

## Уровни пользователей

| Уровень | Константа | Описание |
|---------|-----------|----------|
| `boss` | `User::BOSS` | Владелец — полный доступ ко всему |
| `admin` | `User::ADMIN` | Администратор |
| `moder` | `User::MODER` | Модератор |
| `editor` | `User::EDITOR` | Редактор — минимальный уровень доступа к AdminPanel |
| `user` | `User::USER` | Обычный пользователь |
| `pended` | `User::PENDED` | Ожидающий подтверждения регистрации |
| `banned` | `User::BANNED` | Заблокированный |

## Группы

```php
User::ADMIN_GROUPS = [editor, moder, admin, boss]  // имеют доступ к AdminPanel
User::USER_GROUPS  = [user, editor, moder, admin, boss]
User::ALL_GROUPS   = [banned, pended, user, editor, moder, admin, boss]
```

## Проверка прав в коде

```php
// Проверить что пользователь — редактор или выше (по умолчанию)
isAdmin();

// Проверить конкретный уровень и выше
isAdmin('moder');   // модератор, админ, босс
isAdmin('admin');   // только админ и босс
isAdmin('boss');    // только владелец

// Получить текущего пользователя
$user = getUser();       // объект User или null
$level = getUser('level'); // значение поля

// Проверить авторизацию
if (getUser()) { ... }
```

## Проверка в Blade-шаблонах

```blade
@if (isAdmin())
    <!-- Видно редакторам и выше -->
@endif

@if (isAdmin('boss'))
    <!-- Только владелец -->
@endif

@if (getUser())
    <!-- Авторизованные пользователи -->
@endif
```

## Проверка в контроллерах

```php
public function edit(): View
{
    abort_if(! isAdmin(), 403);
    // ...
}

public function destroy(): RedirectResponse
{
    abort_if(! isAdmin('admin'), 403);
    // ...
}
```

## Управление пользователями

Роли назначаются в AdminPanel → Пользователи → редактировать пользователя.

Принцип иерархии: каждый уровень включает все права уровней ниже. Например, `moder` проходит проверку `isAdmin('editor')`, но не `isAdmin('admin')`.
