# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 13 / PHP 8.3 e-commerce app. Admin is the active build surface; storefront files are mostly view stubs unless a task explicitly targets frontend work.

- `app/Http/Controllers/Backend`: admin controllers.
- `app/Services`: business logic, query workflows, exports, bulk actions, and multi-step persistence.
- `app/Models`, `app/Policies`, `app/Enums`: domain models, authorization, and typed state.
- `resources/views/admin`: admin Blade screens and partials.
- `resources/css/app.css`, `resources/js/app.js`: Vite-built admin styling and shared JS.
- `routes/web.php`: frontend and admin route groups.
- `tests/Feature`: Pest feature tests using `RefreshDatabase`.
- `docs/`: roadmap and admin CRUD guidance.

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

Use PSR-4 namespaces under `App\`. PHP code should use typed returns, constructor property promotion where useful, and Laravel conventions. Controllers should stay thin: validate, authorize, call a service, and return a response. Put query composition, exports, bulk mutations, transactions, image/media workflows, and cross-model logic in `app/Services`.

Admin resources follow `Backend\FooController`, `StoreFooRequest`, `UpdateFooRequest`, `FooPolicy`, and `resources/views/admin/foos/*`. Reuse shared Blade components instead of hand-rolling table/filter UI.

## Testing Guidelines

Tests use Pest. Place feature tests in `tests/Feature` and name files by behavior, for example `AdminCustomerManagementTest.php`. Prefer tests around routes, authorization, validation, and important service workflows. Run `php artisan test` before handing off changes.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit style, for example `feat: add activity log management`. Use short prefixes such as `feat:`, `fix:`, `refactor:`, or `docs:`. PRs should include a clear summary, affected admin pages/routes, migration notes, test results, and screenshots for UI changes.

## Security & Configuration Tips

Do not commit `.env`, generated secrets, or uploaded media. Admin authorization should use policies and seeded spatie permissions; avoid adding new route-level role middleware unless matching existing Roles/Permissions behavior.
