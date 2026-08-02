# Changelog

## 1.0.3
- Контроллеры переведены с устаревших `setFlash()`/`setInput()` на `redirect()->with()` и `->withInput()->withErrors()`: ошибки валидации отдаются редиректом (PRG), обновление страницы после ошибки не отправляет форму повторно

## 1.0.2
- Добавлены smoke-тесты (`Tests/Feature/TemplateSmokeTest.php`) — пример тестирования модуля
- README дополнен английской версией и разделом про тесты

## 1.0.1
- Поле `templates.created_at` переведено с int (unix-таймстамп) на `DATETIME` (исторически-корректная конверсия таймзон, пересоздание индекса `created_at`)
- `$timestamps = false` заменён на `const UPDATED_AT = null` (updated_at-колонки нет, created_at заполняется авто), убран ручной `SITETIME`
- Требует ядро 14.1.0

## 1.0.0
- Первый релиз
