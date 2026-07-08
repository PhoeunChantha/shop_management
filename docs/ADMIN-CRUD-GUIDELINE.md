# Admin CRUD Guideline

The house pattern for every admin resource in this project. Follow it verbatim when
adding or refactoring an admin CRUD so all resources stay consistent.

**Canonical reference implementation:** `Brand` (model, controller, requests, views) —
including the delete guard + bulk actions. Other resources following this: `Category`,
`Color`, `Size`, `Coupon`, `Product`, `Attribute`.

---

## Checklist — adding a new admin resource `Foo`

1. **Migration** — `database/migrations/*_create_foos_table.php`; add indexes for
   columns you filter/sort on (`status`, dates, etc.).
2. **Model** — `app/Models/Foo.php`: `$fillable`, `$casts` (enums, decimals,
   datetimes, booleans), relationships, a **parameterized `scopeSearch`**, and any
   domain helpers.
3. **Enum** (if the model has a fixed set field) — `app/Enums/FooType.php`, backed,
   with `label()` / `options()`; cast it on the model.
4. **Form Requests** — `app/Http/Requests/Foo/` → `BaseFooRequest`, `StoreFooRequest`,
   `UpdateFooRequest`. `authorize()` returns `true` — the **Policy** is the gate.
5. **Policy + permissions** — `app/Policies/FooPolicy.php` extends `AdminRolePolicy`
   with `protected string $subject = 'foos';` (auto-discovered by Laravel). Add `foos`
   to the `$subjects` array in `database/seeders/RolePermissionSeeder.php`, then
   `php artisan db:seed --class=RolePermissionSeeder` and `php artisan permission:cache-reset`.
6. **Controller** — `app/Http/Controllers/Backend/FooController.php`; call
   `$this->authorize(...)` at the top of every action (no controller-level middleware).
7. **Routes** — a `Route::prefix('foos')->name('foos.')->group(...)` inside the
   `admin` (`auth`) group in `routes/web.php`. **No role/permission middleware** —
   the Policy authorizes (index/create/store/edit/update/destroy).
8. **Views** — `resources/views/admin/foos/`: `index`, `create`, `edit`, `_form`.
   (Use a `_modal` instead of create/edit pages only when the form is ~3 fields —
   see Brand.)
9. **Sidebar** — add a link in `resources/views/admin/layouts/sidebar.blade.php`,
   include the route in its section's active-state check.
10. **Run** — `php artisan migrate` and verify `php artisan route:list --name=admin.foos`.
11. **(Optional) Bulk actions & delete guard** — for catalog tables, add
    `Model::isInUse()`, the `bulkDestroy`/`bulkStatus` methods + routes, and the
    checkbox column + `<x-bulk-bar>`. See *Bulk actions & delete guards* below.

---

## Model

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Foo extends Model
{
    protected $fillable = ['name', 'slug', 'image', 'status'];

    protected $casts = [
        'status' => 'boolean',
        // 'type' => FooType::class,  'price' => 'decimal:2',  'starts_at' => 'datetime',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Whether this record is referenced elsewhere (blocks deletion). The bulk
     * delete + single destroy both honour this. Return false if never guarded.
     */
    public function isInUse(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Filter by a search term. Term is PASSED IN — never read request() in a model.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
        );
    }
}
```

Rules:
- **Scopes take parameters** (`?string $term`), never call `request()` inside the
  model — keeps scopes reusable and testable.
- Put domain logic on the model as helpers (e.g. `Coupon::isValid()`,
  `discountFor()`), not in controllers.

---

## Form Requests

`BaseFooRequest` holds shared `rules()`. `Store`/`Update` extend it; `Update`
overrides `fooId()` to ignore its own row on unique checks. **`authorize()` returns
`true`** — authorization lives in the Policy (see *Authorization* below), not here.

```php
abstract class BaseFooRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the resource Policy in the controller.
        return true;
    }

    protected function fooId(): ?int
    {
        return null;
    }

    // Normalise input BEFORE validation (uppercase codes, trim, etc.)
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('foos', 'code')->ignore($this->fooId())],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'status' => ['required', 'boolean'],
        ];
    }

    // Cross-field checks (e.g. percentage <= 100, end after start)
    public function withValidator(Validator $validator): void { /* ->after(...) */ }
}
```

```php
class StoreFooRequest extends BaseFooRequest {}

class UpdateFooRequest extends BaseFooRequest
{
    protected function fooId(): ?int
    {
        return (int) $this->route('id');
    }
}
```

---

## Controller

```php
class FooController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Foo::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $foos = Foo::query()
            ->withCount('products')      // when useful before delete
            ->search($search)            // pass the term explicitly
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.foos.index', ['foos' => $foos, 'perPage' => $perPage]);
    }

    public function create(): View
    {
        $this->authorize('create', Foo::class);

        return view('admin.foos.create');
    }

    public function store(StoreFooRequest $request): RedirectResponse
    {
        $this->authorize('create', Foo::class);

        try {
            $validated = $request->safe()->except('image');   // image handled separately
            $validated['slug'] = $this->uniqueSlug($validated['name']);

            $foo = Foo::create($validated);

            if ($request->hasFile('image')) {
                $foo->image = ImageManager::upload($request->file('image'), 'foos');
                $foo->save();
            }

            return to_route('admin.foos.index')->with('success', 'Foo created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating foo: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('image'),
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'An error occurred while creating the foo.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Foo::class);

        return view('admin.foos.edit', ['foo' => Foo::findOrFail($id)]);
    }

    public function update(UpdateFooRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Foo::class);

        try {
            $foo = Foo::findOrFail($id);

            $validated = $request->safe()->except('image');
            $validated['slug'] = $this->uniqueSlug($validated['name'], $foo->id);

            $foo->update($validated);

            if ($request->hasFile('image')) {
                $foo->image = ImageManager::update($request->file('image'), $foo->image, 'foos');
                $foo->save();
            }

            return to_route('admin.foos.index')->with('success', 'Foo updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating foo: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('image'),
                'foo_id' => $id,
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'An error occurred while updating the foo.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Foo::class);

        try {
            $foo = Foo::findOrFail($id);

            if ($foo->isInUse()) {
                // Flash to session('error') — NOT withErrors — so <x-toastr> shows it
                // (the error bag isn't rendered on index pages).
                return back()->with('error', "Cannot delete “{$foo->name}” because it is still in use.");
            }

            ImageManager::delete($foo->image, 'foos');
            $foo->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting foo: '.$e->getMessage(), ['exception' => $e, 'foo_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the foo.']);
        }

        return to_route('admin.foos.index')->with('success', 'Foo deleted successfully!');
    }

    // ---- Bulk actions (see the "Bulk actions & delete guards" section) ----

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Foo::class);

        $ids = $this->validatedIds($request);            // from HandlesBulkActions trait
        $result = $bulk->destroy(Foo::class, $ids, 'foos');   // 'foos' = image folder (or null)

        return back()->with($this->bulkFlash($result, 'foo', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Foo::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Foo::class, $ids, $status);

        return back()->with('success', $count.' foo(s) '.($status ? 'enabled' : 'disabled').'.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string { /* … */ }
}
```

Non-negotiables:
- **A route action is a controller method.** Prefer the standard RESTful actions
  (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy` — use the subset
  needed; `show` only for a detail page). When a route needs an **extra** action
  (e.g. `updateStatus`, `destroyImage`, `export`), add it as **another method on the
  same controller** — *don't* create a separate single-action/invokable controller
  for it. Keep it thin: the method **validates and delegates to a service**; the
  business logic lives in `app/Services`. `private` helpers (`uniqueSlug`,
  `syncValues`) are fine.
- **Authorize every action via the Policy.** Call `$this->authorize('viewAny|create|
  view|update|delete', Foo::class)` as the first line of each action. **No
  controller-level middleware and no role/permission middleware on the routes** — the
  Policy is the single gate. Form Request `authorize()` returns `true`.
- **Type-hint the Form Request**; it owns validation only (authorization is the Policy's).
- **`try/catch` + `Log::error`** on `store`/`update`/`destroy`; on failure return
  `back()->withInput()->withErrors(['error' => '…'])`. Never dump the image file into
  logs — use `$request->except('image')`.
- **`to_route()`** for redirects; typed returns (`View` / `RedirectResponse`).
- **Mass-assign** with `create()` / `update()`. If a column is a file (fillable
  `image`), **exclude it** (`$request->safe()->except('image')`) and let
  `ImageManager` own that column; guard writes with `hasFile()`.
- **`index()`** validates `search` + `per_page` inline and uses `->search($search)`.

---

## Authorization (Policies + spatie permissions)

Authorization is **Policy-based**. Each resource has a thin Policy extending
`AdminRolePolicy`, whose abilities map to granular spatie permissions built from a
`$subject`: `view {subject}`, `create {subject}`, `edit {subject}`, `delete {subject}`.

```php
// app/Policies/FooPolicy.php  — auto-discovered as the policy for App\Models\Foo
class FooPolicy extends AdminRolePolicy
{
    protected string $subject = 'foos';
}
```

`AdminRolePolicy` (the base) checks `hasPermissionTo("… {$subject}")`:
`viewAny`/`view` → `view`, `create` → `create`, `update` → `edit`, `delete` → `delete`.
Override a method in the concrete policy for special cases.

**Wiring:**
- The base `Controller` uses the `AuthorizesRequests` trait, so `$this->authorize(...)`
  works in every controller.
- Controllers call `$this->authorize(ability, Foo::class)` per action; routes carry
  only `auth`; Form Requests `authorize()` return `true`.

**Permissions live in `RolePermissionSeeder`** — add the new resource to the
`$subjects` array (it generates `view/create/edit/delete {subject}`), then:

```bash
php artisan db:seed --class=RolePermissionSeeder   # admin gets every permission
php artisan permission:cache-reset                 # spatie caches permissions
```

Notes:
- The `admin` role is granted **all** permissions; the `user` role (storefront
  customers) gets **none** — so only admins reach the panel. New granular roles
  (e.g. `manager`) can be given a subset.
- `hasPermissionTo` throws if a permission doesn't exist, so always seed the new
  `{subject}` permissions before shipping a resource.

---

## Views

- Full pages: `index` + `create` + `edit` + shared `_form` partial (`@include`d with
  `mode`, `action`, `submitText`). Use a `_modal` (see Brand) only for ~3-field forms.
- Reuse the shared Blade components — never hand-roll table/filter chrome:
  `x-table-loader`, `x-table-toolbar`, `x-table-footer`, `x-search-input`,
  `x-per-page-selector`, `x-select`, `x-image-upload`, `x-filter-card`,
  `x-delete-confirm-modal`, `x-table-actions` (row action dropdown), `x-bulk-bar`
  (bulk action bar — see *Bulk actions & delete guards*).
- Table search/per-page/filter are **GET** forms that submit via
  `form.requestSubmit()` (so the `submit` event fires for `x-table-loader`).
  Search inputs opt in with `<x-search-input>` or `data-auto-search`.
- Images render with the global `Imageurl($model->image, 'foos')` helper.

---

## Bulk actions & delete guards

Catalog tables support **row selection + bulk delete / enable / disable**, and both
single and bulk delete honour a `isInUse()` guard so referenced records can't be
orphaned. The moving parts (all reusable — wire them up, don't re-implement):

**Backend**
- **`Model::isInUse(): bool`** — returns whether the record is referenced (products,
  variants, …). Add it to any model that should be protected; omit for models that
  are always safe to delete.
- **`App\Services\BulkActionService`** — `destroy($modelClass, $ids, $imageFolder = null)`
  (skips + reports anything whose `isInUse()` is true, deletes images when a folder is
  given) and `setStatus($modelClass, $ids, bool)`.
- **`App\Http\Controllers\Backend\Concerns\HandlesBulkActions`** — `use` it in the
  controller. Provides `validatedIds()`, `validatedStatus()` and `bulkFlash($result,
  $noun, $reason)` (builds the success / “skipped N still …” toast payload).
- **Controller** gets `bulkDestroy()` + `bulkStatus()` (see the controller example),
  each `authorize()`-d and delegating to the service.
- **Routes** — add **before** the `/{id}` routes in the group so `/bulk` isn't
  captured as an id:
  ```php
  Route::delete('/bulk', [FooController::class, 'bulkDestroy'])->name('bulk-destroy');
  Route::patch('/bulk-status', [FooController::class, 'bulkStatus'])->name('bulk-status');
  ```
  Omit `bulk-status` for resources with no `status` column.

**Frontend**
- Wrap the table `<section class="premium-card">` in `x-data="bulkSelect()"` (Alpine
  factory registered in `app.js` — shared `selected` array + select-all state).
- First `<th>`/`<td>` is a checkbox column:
  ```blade
  <th class="bulk-check-col">
      <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
          :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
  </th>
  {{-- per row --}}
  <td class="bulk-check-col">
      <input type="checkbox" class="bulk-check" data-row-check value="{{ $foo->id }}"
          x-model="selected" aria-label="Select row">
  </td>
  ```
  Row checkboxes **must** carry `data-row-check` + `value="{id}"`; bump the empty-state
  `colspan` by 1.
- Drop `<x-bulk-bar>` directly inside the `x-data` section (e.g. right after
  `<x-table-loader />`) — **not** in the toolbar. It renders as a `position: fixed`
  popup pinned to the bottom-centre of the page, plus the confirm modal, shown only
  when rows are selected:
  ```blade
  <x-bulk-bar :destroy="route('admin.foos.bulk-destroy')"
      :status="route('admin.foos.bulk-status')" noun="foo" />
  ```

Delete/guard messages flash via **`session('error'|'success'|'warning'|'info')`** so
`<x-toastr>` surfaces them (the `$errors` bag is not rendered on index pages).

---

## Reminders

- After editing `resources/css/app.css` or `resources/js/app.js`, run
  `npm run dev` / `npm run build`. Blade-only changes need `php artisan view:clear`
  if compiled views look stale.
- Admin work comes before storefront — see `docs/ROADMAP.md`.
