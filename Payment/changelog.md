# Changelog

## 1.0.1
- Поля `paid_adverts.created_at`/`deleted_at` (срок размещения) переведены с int (unix-таймстамп) на `DATETIME` (исторически-корректная конверсия таймзон)
- `PaidAdvert`: `$timestamps = false` заменён на `const UPDATED_AT = null` (updated_at-колонки нет, created_at заполняется авто), каст `deleted_at => datetime`; сравнения срока переведены на Carbon/`now()`
- `Order` и таблица `orders` уже на `DATETIME` (создавались позже) — без изменений
- Требует ядро 14.1.0

## 1.0.0
- Первый релиз
