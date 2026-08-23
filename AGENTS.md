# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 13 / PHP 8.3 e-commerce app with **two fully built surfaces**: a deep admin panel and a complete storefront (listing, detail, cart, checkout, ABA PayWay payments, wallet, wishlist, reviews, addresses, orders, invoices). See [CLAUDE.md](CLAUDE.md) for the canonical architecture notes.

- `app/Http/Controllers/Backend` + `app/Http/Controllers/Frontend`: admin and storefront controllers.
- `app/Services/Admin` + `app/Services/Frontend`: business logic split by surface. Query composition, exports, bulk mutations, transactions, media workflows, and cross-model logic live here, not in controllers.
- `app/Models`, `app/Policies`, `app/Enums`: domain models, policy-based authorization, and typed state.
- `resources/views/admin`, `resources/views/frontend`: Blade screens/partials per surface.
- `resources/css/app.css`, `resources/js/app.js`: Vite-built styling and shared JS (both surfaces).
- `routes/web.php`: `frontend.` and `admin.` route groups; auth scaffolding in `routes/auth.php`.
- `tests/Feature`: Pest feature tests using `RefreshDatabase`.
- `docs/`: [ROADMAP.md](docs/ROADMAP.md) (build plan), [ADMIN-CRUD-GUIDELINE.md](docs/ADMIN-CRUD-GUIDELINE.md) (the house CRUD pattern), [ECOMMERCE_IMPROVEMENT_PROPOSALS.md](docs/ECOMMERCE_IMPROVEMENT_PROPOSALS.md) (prioritized backlog).

## Build, Test, and Development Commands

- `composer setup`: install dependencies, create `.env`, run migrations, install npm packages, and build assets.
- `composer dev`: run Laravel server, queue listener, logs, and Vite together.
- `npm run dev`: start Vite only.
- `npm run build`: compile production assets after CSS/JS changes.
- `php artisan migrate`: apply database migrations.
- `php artisan test` or `composer test`: run Pest tests.
- `./vendor/bin/pint`: format PHP with Laravel Pint.
- `php artisan view:clear`: clear stale compiled Blade views.

## Coding Style & Naming Conventions

Use PSR-4 namespaces under `App\`. PHP code should use typed returns, constructor property promotion where useful, and Laravel conventions. Controllers should stay thin: validate, authorize, call a service, and return a response. Put query composition, exports, bulk mutations, transactions, image/media workflows, and cross-model logic in `app/Services/Admin` or `app/Services/Frontend`.

Admin resources follow `Backend\FooController`, `BaseFooRequest`/`StoreFooRequest`/`UpdateFooRequest`, `FooPolicy`, and `resources/views/admin/foos/*` — see [docs/ADMIN-CRUD-GUIDELINE.md](docs/ADMIN-CRUD-GUIDELINE.md). Reuse shared Blade components instead of hand-rolling table/filter UI.

## Testing Guidelines

Tests use Pest. Place feature tests in `tests/Feature` and name files by behavior, for example `AdminCustomerManagementTest.php`. Prefer tests around routes, authorization, validation, and important service workflows. Run `php artisan test` before handing off changes.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit style, for example `feat: add activity log management`. Use short prefixes such as `feat:`, `fix:`, `refactor:`, or `docs:`. PRs should include a clear summary, affected admin pages/routes, migration notes, test results, and screenshots for UI changes.

## Security & Configuration Tips

Do not commit `.env`, generated secrets, or uploaded media. Admin authorization uses policies — each `FooPolicy` extends `AdminRolePolicy` with a `protected string $subject` (e.g. `'products'`) and gates `viewAny`/`view`/`create`/`update`/`delete` against `{action} {subject}` permissions seeded in `RolePermissionSeeder`. Add route-level role/permission middleware only where the existing Roles/Permissions routes already do.
