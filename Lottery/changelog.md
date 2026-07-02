# Changelog

## 1.0.1
- Поле `lottery_users.created_at` переведено с int (unix-таймстамп) на `DATETIME` (исторически-корректная конверсия таймзон)
- Включены авто-таймстампы вместо `$timestamps = false`: `const UPDATED_AT = null` (updated_at-колонки нет, created_at заполняется авто), убран ручной `SITETIME`
- Колонка `lottery.day` (тип `DATE`) и модель `Lottery` (без дат-таймстампов) не затронуты
- Требует ядро 14.1.0

## 1.0.0
- Первый релиз
