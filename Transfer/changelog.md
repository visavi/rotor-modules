# Changelog

## 1.0.2
- Поле `transfers.created_at` переведено с int (unix-таймстамп) на `DATETIME` (исторически-корректная конверсия таймзон, пересоздание индекса `created_at`)
- `$timestamps = false` заменён на `const UPDATED_AT = null` (updated_at-колонки нет, created_at заполняется авто), убран ручной `SITETIME`
- Требует ядро 14.0.3

## 1.0.1
- Исправлены переводы (en, ua)

## 1.0.0
- Первый релиз
