# Laravel Upgrade Plan

This repository is currently running Laravel 5.4.36 on PHP 7.4.33.

Current constraint: keep PHP at `7.4.33`.

Because of that constraint, Laravel 12 is not a valid destination for this repository unless PHP is upgraded later. The highest practical Laravel target under the current PHP baseline is Laravel `8.x`.

## Phase 1: Completed

- Move legacy model classes from `app/` to `app/Models/`
- Update model namespaces from `App\...` to `App\Models\...`
- Update controller imports and class references
- Update auth and service configuration to use `App\Models\User`

## Phase 2: Platform Baseline

- Raise PHP target to a Laravel 12 compatible version
- Audit server runtime, Composer version, IIS PHP handler, and required extensions
- Remove packages that are not Laravel 12 compatible
- Baseline audit recorded in `docs/phase2-platform-baseline.md`

Note:

- If PHP remains on `7.4.33`, skip the Laravel 12 target and retarget the upgrade plan to Laravel `8.x`

## Phase 3: Framework Upgrade Path

- Upgrade incrementally instead of jumping directly from Laravel 5.4
- Recommended major-version path:
  - Laravel 5.4 -> 5.8
  - Laravel 5.8 -> 6 LTS
  - Laravel 6 -> 8
- At each step:
  - update `composer.json`
  - run upgrade-guide fixes
  - clear caches
  - run Artisan sanity checks

If PHP stays on `7.4.33`, stop at Laravel `8.x`.

If PHP is upgraded in the future, continue from Laravel `8.x` toward newer majors.

## Phase 4: Modern Conventions

- Replace string-based controller routing with class-based route definitions
- Move route registration toward Laravel 12 service provider conventions
- Review middleware registration changes
- Review exception handling bootstrap changes
- Replace deprecated helper usage and legacy facades where needed
- Migrate factories from legacy `ModelFactory.php` style to class-based factories
- Review auth scaffolding and password reset flow

## Phase 5: Application Refactor

- Replace legacy query-builder-heavy logic with service or action classes where useful
- Add explicit model relationships
- Add request validation classes for large form handlers
- Split oversized controllers into smaller domain-focused controllers

## Phase 6: Verification

- Run application smoke tests on login, ordering, admin, reporting, and popup flows
- Validate database migrations and session/auth behavior
- Verify IIS deployment settings against the upgraded bootstrap structure
