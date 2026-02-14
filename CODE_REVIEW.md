# Code Review Report

Date: 2026-02-13
Repository: `bidb97/laravel-query-explain`

## Scope

Reviewed package bootstrap, routing, middleware wiring, AST tooling, DTO/VO layer, and controller/service integration.

## Summary

The package currently has **multiple release-blocking defects** that prevent installation and/or runtime execution:

1. A hard parse error in `Analyzer.php`.
2. Widespread namespace drift (`explain\src\...`) that does not match Composer PSR-4 autoload (`Bidb97\QueryExplain\...`).
3. Route and config class references use incorrect namespaces, so controller/middleware resolution will fail.
4. Several classes type-hint unresolved symbols from the wrong namespace.
5. Incomplete dead method (`Finder::getQuery`) and other quality gaps.

---

## Findings

### 1) **Critical**: Parse error in analyzer import list
- `src/Tools/Analyzer.php` contains an invalid `use` statement (`packages\laraveluse packages\laraveluse ...`) that causes immediate parse failure.
- Evidence: `php -l src/Tools/Analyzer.php` fails with syntax error.

**Impact**: Package cannot be loaded where this file is parsed.

### 2) **Critical**: Namespace mismatch across the package
- Many files import or reference classes under `explain\src\...` even though Composer autoload maps only `Bidb97\QueryExplain\`.
- Affected examples include manager, finder, analyzer, VO classes, controller, routes, and config.

**Impact**: Class resolution/type-hinting fails at runtime; service container injection and route/controller wiring break.

### 3) **Critical**: Route/controller binding uses wrong namespace
- `routes/web.php` references `explain\src\Http\Controllers\QueryExplainController` instead of package namespace.

**Impact**: Route target class not found when route is resolved.

### 4) **Critical**: Middleware config points to wrong class
- `config/query-explain.php` points middleware to `explain\src\Http\Middleware\Authorize::class`.

**Impact**: Middleware class cannot be resolved from config during route registration/execution.

### 5) **High**: Query manager imports wrong DTO/tool classes
- `src/Services/QueryExplainManager.php` imports `explain\src\Tools\Analyzer`, `explain\src\Tools\Finder`, and `explain\src\DTO\Query`.

**Impact**: Constructor DI and return types refer to unresolved classes.

### 6) **Medium**: Incomplete/unused method in finder
- `src/Tools/Finder.php` contains an empty `getQuery()` method with no implementation.

**Impact**: Dead API surface and maintainability concern; can mislead users and future contributors.

### 7) **Medium**: VO layer depends on wrong namespaced interfaces/enums
- `Root` and `Execute` reference `explain\src\...` types throughout properties, constructor params, and match expressions.

**Impact**: Domain object creation and behavior fail if autoload cannot resolve those types.

---

## Recommended Fix Plan (ordered)

1. **Fix syntax error first** in `Analyzer.php` so static checks can proceed.
2. **Global namespace normalization** from `explain\src\...` to `Bidb97\QueryExplain\...` for all package files.
3. Re-run:
   - `php -l` for all PHP files,
   - package smoke test (`composer dump-autoload` + basic instantiation in testbench).
4. Remove or implement `Finder::getQuery()`.
5. Add CI checks:
   - lint (`php -l` via script),
   - static analysis (PHPStan/Psalm),
   - minimal package integration tests with Orchestra Testbench.

## Validation commands run during review

- `for f in $(rg --files src config routes resources/views README.md); do if [[ $f == *.php ]]; then php -l "$f" || true; fi; done`
- `rg -n 'explain\\src|packages\\laraveluse|getQuery\(\)' src`

