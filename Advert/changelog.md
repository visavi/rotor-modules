# Changelog

## 1.0.2
- Поля `created_at`/`deleted_at` (срок действия рекламы) переведены с int (unix-таймстамп) на `DATETIME` (исторически-корректная конверсия таймзон)
- Включены авто-таймстампы вместо `$timestamps = false`: `const UPDATED_AT = null` (колонки updated_at нет, created_at заполняется авто); `deleted_at` (expiry) проставляется вручную через `now()->addHours/addDays`
- Сравнения срока действия переведены на Carbon/`now()`
- Требует ядро 14.0.3

## 1.0.1
- Исправлены переводы (en, ua)

## 1.0.0
- Первый релиз
