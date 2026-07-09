# Laravel Upgrade Plan

**Status update:** this doc originally described the repo as stuck on
Laravel 5.4.36 / PHP 7.4.33 with Laravel 12 blocked pending a PHP
upgrade. That's no longer the case — PHP and the framework have since
been upgraded and confirmed live: `php -v` reports `PHP 8.2.32`,
`php artisan --version` reports `Laravel Framework 12.62.0`, and
`composer.lock` pins `laravel/framework v12.62.0`. Phases 1-3 below
are complete. See `docs/phase2-platform-baseline.md` for the historical
baseline that prompted this plan.

## Phase 1: Completed

- Move legacy model classes from `app/` to `app/Models/`
- Update model namespaces from `App\...` to `App\Models\...`
- Update controller imports and class references
- Update auth and service configuration to use `App\Models\User`

## Phase 2: Completed

- Raised PHP to 8.2.32 (Laravel 12 compatible) and upgraded Composer
- Removed packages that weren't Laravel 12 compatible
  (`phpoffice/phpexcel`, `fzaninotto/faker`, old `phpunit`/`mockery`)
- Baseline audit recorded in `docs/phase2-platform-baseline.md`

## Phase 3: Completed

- `composer.json` was upgraded directly to `laravel/framework ^12.0`
  (skipping the incremental 5.4 -> 5.8 -> 6 -> 8 path originally
  planned here, since the PHP blocker that motivated a staged path was
  resolved before this phase started)
- `vendor/` and `composer.lock` confirm Laravel 12.62.0 is installed
  and boots correctly (`php artisan route:list` works, all existing
  routes resolve)

## Phase 4: Modern Conventions

- [x] Replace string-based controller routing with class-based route
      definitions (`routes/web.php` now uses `[Controller::class,
      'method']->name(...)` throughout instead of the Laravel 5-era
      `Route::namespace(...)->group()` + `['as' => ..., 'uses' =>
      'Controller@method']` arrays)
- [x] Add real auth middleware (`admin.auth`/`user.auth`) instead of
      per-method `session('admin_id')`/`session('user_id')` checks;
      routes are now grouped by public/user/admin instead of one flat
      group
- [ ] Review middleware registration changes beyond the above
- [ ] Review exception handling bootstrap changes
- [ ] Replace deprecated helper usage and legacy facades where needed
- [ ] Migrate factories from legacy `ModelFactory.php` style to
      class-based factories
- [ ] Review auth scaffolding and password reset flow — note:
      `App\Http\Controllers\Auth\*` (Laravel-UI scaffold),
      `App\Models\User`, and the `users` table are currently dead code
      not reachable from any route; the real app auth is 100% custom
      session-key-based auth against `admins`/`gen_users`. Decide
      whether to remove the unused scaffold or actually wire it up.

## Phase 5: Application Refactor

- [ ] Replace legacy query-builder-heavy logic with service or action
      classes where useful
- [ ] Add explicit model relationships
- [ ] Add request validation classes for large form handlers
- [ ] Split oversized controllers into smaller domain-focused
      controllers — `AdminController` (~1100 lines),
      `DashboardController` (~735 lines), `AdminReportController`
      (~565 lines), and `AdminUsersReportController` (~465 lines) are
      the main targets
- [ ] Strip the now-redundant inline `session('admin_id')`/
      `session('user_id')` checks from controller methods now that
      `admin.auth`/`user.auth` middleware enforces this centrally
      (kept as harmless defense-in-depth for now; removing them is
      lower-risk once the middleware has been live for a while)

## Phase 6: Verification

- [x] Passwords hashed with bcrypt (`App\Support\PasswordHasher`)
      instead of raw `md5()`, with legacy rows self-upgrading on next
      login
- [x] Admin/user session auth enforced via middleware, closing several
      previously-unauthenticated `AdminController` endpoints
      (`ajaxDatatableUsersDetails`, `changeBasicDetails`,
      `changePersonalDetails`, `changeUserAccessStatus`,
      `changeUniformEnableDisable`, `changeUserAccessBlockAll`,
      `changeUserAccessUnblockAll`, `saveUniformEditedDetails`)
- [x] `route:list` diffed before/after the routing rewrite — identical
      URIs/names/actions
- [ ] Run application smoke tests on login, ordering, admin, reporting,
      and popup flows beyond what's covered by the new
      `tests/Feature/AdminAuthTest.php` / `UserAuthTest.php`
- [ ] Validate database migrations and session/auth behavior more
      broadly
- [ ] Verify IIS deployment settings against the upgraded bootstrap
      structure
