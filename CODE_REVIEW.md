# Отчёт по код-ревью

Дата: 2026-02-13  
Репозиторий: `bidb97/laravel-query-explain`

## Область проверки

Проверены: bootstrap пакета, маршруты, middleware, AST-инструменты, слой DTO/VO, интеграция контроллеров и сервисов.

## Краткий итог

Сейчас в пакете есть **несколько блокирующих проблем**, из-за которых установка и/или выполнение ломаются:

1. Критическая синтаксическая ошибка в `Analyzer.php`.
2. Массовое расхождение namespace (`explain\src\...`) с PSR-4 autoload (`Bidb97\QueryExplain\...`).
3. Неверные namespace в ссылках на контроллер и middleware в маршрутах/конфиге.
4. Типы и импорты в ряде классов указывают на несуществующие классы.
5. Пустой незавершённый метод `Finder::getQuery()`.

---

## Найденные проблемы

### 1) **Critical**: синтаксическая ошибка в `Analyzer`
- В `src/Tools/Analyzer.php` есть некорректный `use`: `packages\laraveluse packages\laraveluse ...`.
- Проверка `php -l` падает с parse error.

**Влияние:** файл не парсится, функциональность анализатора недоступна.

### 2) **Critical**: неправильные namespace по проекту
- Во многих файлах используются ссылки на `explain\src\...`, хотя в `composer.json` PSR-4 задан как `Bidb97\QueryExplain\`.
- Это встречается в manager/finder/analyzer, VO, контроллере, маршрутах и конфиге.

**Влияние:** DI, автозагрузка и разрешение классов ломаются во время выполнения.

### 3) **Critical**: маршруты указывают на неправильный контроллер
- `routes/web.php` использует `explain\src\Http\Controllers\QueryExplainController`.

**Влияние:** роуты не смогут разрешить target-класс контроллера.

### 4) **Critical**: middleware в конфиге указывает на несуществующий класс
- `config/query-explain.php` содержит `explain\src\Http\Middleware\Authorize::class`.

**Влияние:** middleware не резолвится, маршрутная группа может падать при инициализации/обработке.

### 5) **High**: неверные импорты в `QueryExplainManager`
- `src/Services/QueryExplainManager.php` импортирует `explain\src\Tools\Analyzer`, `explain\src\Tools\Finder`, `explain\src\DTO\Query`.

**Влияние:** типы конструктора/возврата ссылаются на несуществующие классы.

### 6) **Medium**: пустой метод `Finder::getQuery()`
- В `src/Tools/Finder.php` объявлен, но не реализован `getQuery()`.

**Влияние:** мёртвый API, вводит в заблуждение и ухудшает поддерживаемость.

### 7) **Medium**: VO-слой завязан на неправильные namespace
- `Root` и `Execute` используют `explain\src\...` в type-hint и внутренней логике.

**Влияние:** доменные объекты не работают при стандартной автозагрузке пакета.

---

## Рекомендованный план исправлений (по порядку)

1. Сначала исправить parse error в `Analyzer.php`.
2. Привести namespace во всём пакете к `Bidb97\QueryExplain\...`.
3. Повторно прогнать проверки:
   - `php -l` по всем PHP-файлам,
   - smoke-тест пакета (`composer dump-autoload` + простая проверка через Testbench).
4. Удалить или реализовать `Finder::getQuery()`.
5. Добавить quality gates в CI:
   - lint,
   - статанализ (PHPStan/Psalm),
   - минимальные интеграционные тесты (Orchestra Testbench).

## Команды, выполненные в рамках ревью

- `for f in $(rg --files src config routes resources/views README.md); do if [[ $f == *.php ]]; then php -l "$f" || true; fi; done`
- `rg -n 'explain\\src|packages\\laraveluse|getQuery\(\)' src`
