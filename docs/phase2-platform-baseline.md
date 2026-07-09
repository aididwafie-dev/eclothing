# Phase 2 Platform Baseline

Originally recorded when this repo was on Laravel 5.4.36 / PHP 7.4.33.
That baseline is superseded: PHP and Composer have since been upgraded
and the framework was updated to Laravel 12 (`composer.lock` and
`vendor/laravel/framework` both report `v12.62.0`, `php artisan
--version` prints `Laravel Framework 12.62.0`, `php -v` prints `PHP
8.2.32`). Kept below for history; see
`docs/laravel12-upgrade-plan.md` for current status and remaining
work.

## Current Runtime (as of this update)

- Laravel CLI boots as `Laravel Framework 12.62.0`
- PHP CLI version is `8.2.32`
- `composer.json` requires `php ^8.2` and `laravel/framework ^12.0`

## Historical Baseline (PHP 7.4.33 / Laravel 5.4.36 era)

- IIS FastCGI was configured to use `C:\php 7.4.33\php-cgi.exe`
- Composer version was `2.9.5`
- Composer diagnostics reported HTTPS certificate problems
  (`curl.cainfo`/`openssl.cafile` pointing at a stale `cacert.pem`,
  `curl error 77`) that blocked reliable package downloads. Since the
  upgrade completed, this is no longer a live blocker, but if a fresh
  environment ever needs provisioning, re-check `php --ini` for a
  valid `curl.cainfo`/`openssl.cafile` before running Composer.
- Dependency risks noted at the time (`phpoffice/phpexcel`,
  `fzaninotto/faker`, `phpunit 5.7`, `mockery 0.9`) are resolved:
  `composer.json` now only requires `phpoffice/phpspreadsheet` (no
  `phpexcel`), `fakerphp/faker`, `phpunit/phpunit ^11.5`, and
  `mockery/mockery ^1.6`. The last live use of the old `PHPExcel`
  classes (in `AnnouncementsController`) was dead/unreachable code and
  has been removed rather than ported.

## Remaining Work

The framework/runtime upgrade itself is done. What's still open is
modernizing the application code to match (see
`docs/laravel12-upgrade-plan.md` Phase 4 onward):

- `routes/web.php` has been converted to `[Controller::class,
  'method']->name(...)` syntax and split into public/user/admin
  middleware groups (done).
- Admin/user "am I logged in" checks are still duplicated inline in
  many controller methods in addition to the new middleware gate
  (`admin.auth`/`user.auth`); removing the redundant inline checks is
  a follow-up, not yet done.
- `AdminController`, `DashboardController`, `AdminReportController`,
  and `AdminUsersReportController` are still large, multi-purpose
  controllers — splitting them into smaller domain-focused classes is
  still open (Phase 5 of the upgrade plan).
