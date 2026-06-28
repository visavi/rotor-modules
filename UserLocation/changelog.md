# Changelog

## 1.0.1
- `user_locations.created_at` переведён с `TIMESTAMP` на `DATETIME` — единый тип дат во всём проекте (без проблемы 2038 и UTC-сдвига сессии)
- Исправлен PHPDoc (`Date` → `CarbonImmutable`). created_at = время последнего визита, проставляется вручную в middleware (`now()`), `$timestamps = false` сохранён
- Требует ядро 14.0.3

## 1.0.0
- Первый релиз
