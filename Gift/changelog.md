# Changelog

## 1.0.1
- Поля дат (`gifts.created_at`, `gifts_users.created_at`/`deleted_at`) переведены с int (unix-таймстамп) на `DATETIME` (исторически-корректная конверсия таймзон)
- Включены авто-таймстампы вместо `$timestamps = false`: `const UPDATED_AT = null` (updated_at-колонки нет, created_at заполняется авто); `deleted_at` (срок действия подарка) проставляется вручную через `now()->addDays`
- Сравнения срока действия переведены на Carbon/`now()`
- Требует ядро 14.1.0

## 1.0.0
- Первый релиз
