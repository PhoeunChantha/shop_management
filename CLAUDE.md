# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A Laravel 13 / PHP 8.3 e-commerce ("shop_management") app. The **admin panel is
the built-out part**; the storefront (frontend) is currently view-only stubs. The
full build plan and sequencing live in `docs/ROADMAP.md` — admin/backend work is
being finished before storefront work begins.

Stack: Laravel 13, SQLite (default `DB_CONNECTION=sqlite`), Vite + Tailwind 3 +
Bootstrap 5 + Alpine 3 + jQuery + FontAwesome + toastr, Pest for tests, Laravel
Breeze auth, spatie/laravel-permission (RBAC), spatie/laravel-translatable.

## Commands

```bash
composer dev          # run server + queue + pail logs + vite concurrently (main dev loop)
composer setup        # first-time: install, key, migrate, npm install, build
npm run dev           # vite dev server (assets)  — REQUIRED after editing app.css / app.js
npm run build         # production asset build
php artisan test      # run Pest suite  (alias: composer test — clears config first)
php artisan test --filter=SomeTest        # single test / method
./vendor/bin/pint     # format PHP (Laravel Pint)
php artisan migrate
php artisan view:clear                    # after Blade changes if compiled views look stale
```

Environment is Windows + PowerShell.

### Asset build is not optional
CSS and JS live in `resources/css/app.css` and `resources/js/app.js` and are
bundled by Vite. Changes there (including all admin styling and the shared
behaviors) **do not appear until `npm run dev`/`build` runs**. Blade-only changes
need no build.

## Architecture

### Two surfaces, one app
- **Backend / admin** — `App\Http\Controllers\Backend\*`, routes under the `admin/`
  prefix with `admin.` names and `auth` middleware (`routes/web.php`). This is the
  developed surface.
- **Frontend / storefront** — `App\Http\Controllers\Frontend\*`, `frontend.` names.
  Currently renders static Blade only (no cart/checkout/order logic yet).
- Auth scaffolding is Breeze (`routes/auth.php`). `bootstrap/app.php` routes
  unauthenticated visitors to `admin.login` vs `frontend.login` based on the URL,
  and sends logged-in users to `admin.dashboard`.

### RBAC
spatie/laravel-permission. Middleware aliases `role`, `permission`,
`role_or_permission` are registered in `bootstrap/app.php`. Admin controllers gate
themselves by implementing `HasMiddleware` and returning
`new Middleware('role:admin|manager', only: [...])`.

### Admin CRUD convention (the dominant pattern — follow it for new resources)
**Full house pattern with copy-paste templates: `docs/ADMIN-CRUD-GUIDELINE.md` —
read it before adding or refactoring any admin resource.** `Brand` is the canonical
reference implementation. In short, each resource (Product, Brand, Category, Color,
Size, Coupon, User, Role, Permission) is built the same way:

1. **Controller** `Backend\{Resource}Controller implements HasMiddleware` — a
   `middleware()` role gate, thin actions using `$request->validated()`, typed
   returns (`View` / `RedirectResponse`). `index()` validates `search`/`per_page`
   inline and paginates with `->withQueryString()`.
2. **Form Requests** in `app/Http/Requests/{Resource}/` as `Base*` + `Store*` +
   `Update*`. `Base*` holds `authorize()` (`$this->user()?->hasAnyRole([...])`) and
   shared `rules()`; `Update*` overrides a protected `{resource}Id()` accessor
   (from `$this->route('id')`) so `Rule::unique(...)->ignore()` skips itself.
   Normalize input in `prepareForValidation()`; cross-field checks in
   `withValidator()`.
3. **Views** under `resources/views/admin/{resource}/`: `index`, `create`, `edit`,
   and a shared `_form` partial (`@include`d by create/edit with `mode`/`action`/
   `submitText`). Brand additionally uses a `_modal` popup instead of full pages.

On validation failure Form Requests redirect to the previous URL with errors +
old input — this is what lets full-page forms repopulate and the Brand modal
reopen (its hidden `form_mode`/`form_action` fields come back via `old()`).

### Shared Blade components (`resources/views/components/`)
`x-filter-card`, `x-table-toolbar`, `x-table-footer`, `x-table-loader` (loading
overlay), `x-search-input`, `x-per-page-selector`, `x-select` (custom Alpine
combobox backed by a hidden native `<select>`), `x-image-upload`,
`x-delete-confirm-modal`. Reuse these rather than hand-rolling table/form chrome.

### Table interactions
Search / per-page / filter forms are plain **GET** forms that reload the page.
They submit via `form.requestSubmit()` (NOT `form.submit()`) so the `submit` event
fires and `x-table-loader` can show its overlay. Auto-search comes from either
`<x-search-input>` (Alpine debounce) or a `data-auto-search` input handled in
`app.js` (debounced `requestSubmit()`).

### Images
`App\Helpers\ImageManager` stores uploads under `public/uploads/{folder}/` and
persists **only the filename**. Always pass the same `{folder}` for a field.
`Imageurl($name, $folder)` (note the capital `I`) is a global helper (autoloaded
via `app/Helpers/functions.php`) used in Blade to resolve the public URL.

### Translations & localization
`Product` uses spatie/laravel-translatable: `name`, `short_description`,
`description`, `seo_title`, `seo_description` are per-language JSON. Forms submit
them as arrays (`name[en]`, `name[km]`); the primary/required language comes from
`SettingService::primaryLanguage()`. UI locale is applied by the `SetLocale`
middleware from `session('locale')` (supported: `en`, `km`), switched via
`/lang/{locale}`.

### Enums, Settings, Services
- Backed enums in `app/Enums` (e.g. `CouponType`, `SettingGroup`) carry
  `label()`/`options()` helpers and are cast on models.
- Site configuration is DB-driven: `Setting` model + `SettingService` +
  `SettingGroup` enum (drives the settings tabs and the language list).
- Heavier persistence logic (multi-step product save, image galleries, variants)
  lives in `app/Services/*` (e.g. `ProductService`); simple CRUDs keep it in the
  controller.

### Frontend JS (`resources/js/app.js`)
Single entry that registers Alpine (`Alpine.start()` at the end), exposes jQuery
globally, configures toastr, and wires delegated jQuery behaviors: `data-auto-search`,
`data-avatar-input` preview, `data-permission-group-select` toggles, and dynamic
permission input rows. Component-specific Alpine factories (e.g. `customSelect`,
`brandFormModal`) are defined inline in their Blade components via `@once`.
