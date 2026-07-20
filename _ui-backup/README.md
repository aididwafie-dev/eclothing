# UI Backup — pre-redesign layout

Snapshot of the UI as it existed **before** the 2026-07-20 redesign
(blue/navy/yellow theme). Restore from here if you want to roll back.

## What is stored

| Backup path | Restores to |
| --- | --- |
| `css/style.css` | `public/front_end/css/style.css` |
| `static-layout/*.blade.php` | `resources/views/static-layout/` |

These five partials + the one stylesheet drive every one of the 81 Blade
views (the project has no `@extends` layout — each view `@include`s
`static-layout/header`, `static-layout/sidebar` or
`static-layout/admin_sidebar`). Restoring them reverts the whole UI.

## Roll back

```sh
# from the project root
cp _ui-backup/css/style.css            public/front_end/css/style.css
cp _ui-backup/static-layout/*.blade.php resources/views/static-layout/
php artisan view:clear
```

Then hard-refresh the browser (Ctrl+F5) to drop the cached stylesheet.

## Roll back with git instead

The pre-redesign state is also tagged:

```sh
git checkout ui-pre-redesign -- public/front_end/css/style.css resources/views/static-layout
```
