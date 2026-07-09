# Phase 2 Platform Baseline

This document records the current machine and dependency baseline before any Laravel framework upgrade work.

## Current Runtime

- Laravel CLI boots as `Laravel Framework 5.4.36`
- PHP CLI version is `7.4.33`
- IIS FastCGI is configured to use `C:\php 7.4.33\php-cgi.exe`
- Composer version is `2.9.5`

## Laravel 12 Gap

- Laravel 12 requires PHP `8.2+`
- The current CLI and IIS runtime are both still on PHP `7.4.33`
- Result: the server runtime must be upgraded before Laravel 12 can be installed or tested properly

## Current Constraint Decision

- The chosen constraint is to keep the current PHP version at `7.4.33`
- Under this constraint, Laravel 12 is not a feasible upgrade target
- The practical maximum target on PHP `7.4` is Laravel `8.x`
- Result: the framework upgrade plan must be redirected from Laravel 12 to a PHP-7.4-compatible Laravel target

## PHP Extension Baseline

The current PHP installation includes the key extensions commonly needed for a modern Laravel upgrade path:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `mbstring`
- `openssl`
- `PDO`
- `pdo_mysql`
- `session`
- `tokenizer`
- `xml`
- `zip`

No immediate extension blocker was found for the upgrade path, but the PHP version itself is still below target.

## Composer / TLS Blockers

Composer diagnostics report HTTPS certificate problems:

- `curl.cainfo` points to `D:/Program Files/PhpWebStudy-Data/server/CA/cacert.pem`
- `openssl.cafile` points to `D:/Program Files/PhpWebStudy-Data/server/CA/cacert.pem`
- Composer fails HTTPS verification with `curl error 77`
- Composer also reports SSL/TLS protection is disabled

Result:

- package downloads and secure updates are not reliable yet
- Composer CA configuration must be corrected before any framework upgrade work

## Dependency Risks

The current direct dependency set contains multiple upgrade blockers:

- `laravel/framework 5.4.36`
- `laravel/tinker 1.0.10`
- `phpoffice/phpexcel 1.8.2`
- `phpoffice/phpspreadsheet 1.29.0`
- `fzaninotto/faker 1.9.2`
- `mockery/mockery 0.9.11`
- `phpunit/phpunit 5.7.27`

### Critical Notes

- `phpoffice/phpexcel` is legacy and incompatible with a Laravel 12 target stack; it should be removed in favor of `PhpSpreadsheet`
- The codebase still actively uses `PHPExcel` classes in `AnnouncementsController`
- `fzaninotto/faker` is legacy and should be replaced by `fakerphp/faker`
- `phpunit/phpunit 5.7` and `mockery/mockery 0.9` are far below current Laravel 12 era tooling
- `phpoffice/phpspreadsheet` is present, but the project is mixed between old `PHPExcel` and newer `PhpSpreadsheet` APIs

## IIS / App Entry

- `public/web.config` contains rewrite rules for front-controller routing
- No app-specific PHP handler override exists in the repository; IIS is using machine-level FastCGI config

## Phase 2 Outcome

Phase 2 confirms two possible paths:

### Path A: Upgrade to Laravel 12

This path is blocked unless PHP is upgraded to `8.2+`.

### Path B: Maintain PHP 7.4.33

This path remains viable, but the framework target must be reduced to Laravel `8.x` instead of Laravel 12.

The required prerequisites for the PHP-7.4-compatible path are:

1. Keep CLI and IIS on PHP `7.4.33`
2. Fix `php.ini` CA certificate settings so Composer HTTPS works normally
3. Replace `PHPExcel` usage with `PhpSpreadsheet`
4. Replace legacy dev packages such as `fzaninotto/faker`, `phpunit 5.7`, and `mockery 0.9`
5. Begin a staged framework upgrade toward the highest PHP-7.4-compatible Laravel version
