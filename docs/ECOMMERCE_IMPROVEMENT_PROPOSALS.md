# E-Commerce Improvement & Feature Proposals

> Generated from a full read-only review of the existing production e-commerce
> system (`shop_management`). Every finding below is grounded in actual code that
> was inspected. Where something could not be fully verified it is marked
> **⚠️ Unable to Verify**.

## Overview

`shop_management` is a **Laravel 13 / PHP 8.3** e-commerce platform (SQLite by
default, Vite + Tailwind 3 + Bootstrap 5 + Alpine 3 + jQuery on the front, Pest
for tests). It is **far more complete than the in-repo `CLAUDE.md` describes** —
`CLAUDE.md` still says "the storefront is currently view-only stubs," but the
codebase now has a **fully working storefront**: product listing, product detail,
cart (guest localStorage + signed-in server cart), a re-priced checkout, ABA
PayWay online payments, an in-house wallet, wishlist, verified-buyer reviews,
addresses, order tracking, PDF invoices, and a customer account area.

**The engineering quality of the commerce core is genuinely strong.** Checkout
re-prices every line server-side, row-locks stock to prevent overselling, and
verifies payments server-to-server before marking an order paid. The admin panel
is extensive (deals, abandoned carts, purchase orders, suppliers, returns,
wallets, finance reports, SEO manager, activity log, media library, command
palette, saved views).

The **single largest structural weakness** is that the storefront product
**listing and search are entirely client-side over the full active-product set
loaded into memory**. That is fine for a small catalog and a hard scalability
wall beyond a few hundred products. Most other findings are incremental.

---

# Priority Legend

- 🔴 **P0** — Critical / Must Fix (correctness, security, data integrity, scale wall)
- 🟠 **P1** — High Value (strong UX / conversion / performance / SEO)
- 🟡 **P2** — Nice to Have (moderate impact)
- 🔵 **P3** — Future / Advanced

---

# 1. Recommended New Features & Fixes

## 1.1 Server-side product listing, search & filtering

### Priority
🔴 **P0** (scalability + performance)

### Category
Technical / Customer Experience / Performance

### Current Situation
Both the homepage and the shop page call
`ProductService::mappedActiveProducts()` with **no limit**
(`app/Services/Frontend/ProductService.php:32`). This loads **every active
product** with `variants`, `variants.color`, `variants.size`, `images`, `brand`,
`category`, `subCategory` eager-loaded, maps each to an array, and passes the
**entire collection** to the view. `ShopController::index`
(`app/Http/Controllers/Frontend/ShopController.php:25`) then derives category /
brand / price / size facets in PHP from that full set. The shop Blade renders
**all products into the DOM** and search/filtering is done **client-side in
JavaScript** (`resources/views/frontend/shop/index.blade.php:20` —
`oninput="filterProducts()"`, `setCat`, `setBrand`, price sliders, `saleOnly`,
etc.).

### Problem / Opportunity
- **No pagination** on the listing — page weight and query cost grow linearly
  with the catalog.
- **Search is not real search** — it is a DOM filter over already-rendered cards,
  so it cannot find products not on the page, has no relevance ranking, no
  typo tolerance, and no server-side "no results" handling.
- On a 500+ product catalog this produces a very large HTML payload, heavy
  eager-load queries, and slow first paint.

### Proposed Solution
Introduce a paginated, server-driven listing:
- `ShopController::index` accepts `search`, `category`, `brand`, `sort`, `price`,
  `page` and returns `->paginate()->withQueryString()` (mirror the admin CRUD
  pattern already used throughout the backend).
- Facet counts computed with `GROUP BY` aggregate queries, not PHP over the full
  set.
- Keep the current client-side interactions as a progressive-enhancement layer,
  but make the server the source of truth.
- Add a proper search: start with `LIKE` on `name`/`sku`, then consider
  SQLite FTS5 / a search index if the catalog grows.

### Business Value
Faster pages → better conversion and SEO; the store can actually scale its
catalog without a rewrite later.

### Technical Impact
Frontend (Blade + JS), Backend (`ShopController`, `ProductService`). No DB schema
change required initially; add indexes on the filter columns (see §6).

### Complexity
Medium

### Dependencies
None hard. Interacts with the shop filter JS.

### Risks
Must preserve existing filter UX; regression risk in the facet UI.

### Evidence
`ProductService::mappedActiveProducts()` (no limit); `ShopController::index`;
`HomeController::index:29`; `resources/views/frontend/shop/index.blade.php`.

### Confidence
High — directly verified.

### Recommendation
**Implement Soon.** This is the one change that protects the whole storefront's
scalability.

### Status — ✅ Implemented (shop listing) · 2026-08-13
The **shop page** (`ShopController@index`) is now fully server-driven:
- `ProductService::filteredProducts()` builds the query in the database
  (search on name/sku/brand/category, category/subcategory/brand facets,
  sale/new/best toggles, max-price, size/color variant filters, sort) and
  returns a `paginate(24)->withQueryString()->through(map)` paginator.
- Facet counts come from `GROUP BY` aggregate queries
  (`categoryFacets()`, `brandFacets()`, `priceRange()`), not PHP over the full set.
- The Blade is now a single GET form; controls set hidden inputs and submit
  (house pattern), active states render from `$filters`, and a custom
  design-system pager replaces client-side "show all".
- New composite indexes `(status, sort_order)` and `(status, price)`
  (migration `2026_08_13_000001`).
- Covered by `tests/Feature/ShopListingTest.php` (5 tests; full suite green, 126/126).

**Follow-ups — ✅ done 2026-08-13:**
- `HomeController@index` now uses `ProductService::homePool()` — a bounded,
  deduplicated pool (best sellers + new + featured + on-sale + newest, ~≤60
  products) instead of the whole catalog. The PHP sectioning is unchanged.
- **Header search is now a real live search.** New JSON endpoint
  `GET /shop-search` (`frontend.shop.search`, `ShopController@search` →
  `ProductService::searchSuggestions`, throttled 60/min) + the header search
  modal fetches it (debounced, `AbortController`) and renders results. Previously
  the modal only showed static suggestion chips. Covered by
  `tests/Feature/StorefrontEnhancementsTest.php`.
- **Correction to the earlier note:** `NavigationService` is *not* a load-all
  problem — it is cached (10 min) and `popularProducts()` is capped at 2. The
  earlier concern was overstated; the only real gap was that the modal had no
  functional search, which is now added.

---

## 1.2 Match checkout variants by explicit variant ID, not fuzzy strings

### Priority
🟠 **P1** (data integrity / correctness)

### Category
Technical / Inventory

### Current Situation
`CheckoutService::placeOrder()` re-prices and decrements stock correctly, but the
**variant it sells is resolved by fuzzy string matching** of the cart's `size`
and `color` labels (`matchVariant()`,
`app/Services/Frontend/CheckoutService.php:331`) — it lowercases and does
`contains` / `str_contains` comparisons against variant value strings.

### Problem / Opportunity
- If the fuzzy match **fails** for a product that *does* have variants,
  `$stockable` becomes `null` (the code only treats single-type products as
  stockable otherwise), so **no stock is decremented** and the price falls back
  to the parent product's `final_price` — a potential **oversell + mispricing**.
- If two variants share overlapping color/size text, the **wrong variant** can be
  matched and decremented.

### Proposed Solution
Carry the concrete `product_variant_id` from the product page → cart → checkout
payload and match on the ID. Keep the string match only as a legacy fallback.
When a variant product has no resolvable variant, **reject the line** rather than
silently selling the parent.

### Business Value
Prevents overselling and incorrect charges — both are direct revenue/trust risks.

### Technical Impact
Frontend cart payload (add `variant_id`), `CartItem` (already has size/color;
add `product_variant_id`), `CheckoutService`. Small migration to add the column.

### Complexity
Medium

### Dependencies
Cart JS, `CartService::sync`, `CartItem` schema.

### Risks
Migration + backfill for existing carts (low — carts are ephemeral).

### Evidence
`CheckoutService::matchVariant()` and the `$stockable` logic at
`CheckoutService.php:194-206`.

### Confidence
High — directly verified.

### Recommendation
**Implement Soon.**

### Status — ✅ Implemented (server + client) · 2026-08-13
- **Server:** `CheckoutService::matchVariant()` prefers an explicit `variant_id`
  (exact, authoritative), fuzzy label matching only as fallback.
- **Client plumbing (now done):** `cart_items.product_variant_id` column
  (migration `2026_08_13_000005`); `ProductService::map()` exposes a
  `variant_index` (size-code|colour-key → variant id); the PDP scope carries
  `data-variant-index`; `main.js` resolves the id from the chosen size+colour and
  stores it on the cart line; `CartService::sync()` persists it (validating it
  belongs to the product, so forged ids are dropped) and `mapItems()` returns it;
  the checkout posts the raw cart lines, so `variant_id` reaches `placeOrder()`.
- **Tests:** sells the exact variant (price+stock) with wrong labels; cart round-trips
  the id; forged ids are ignored. **Browser QA note:** the end-to-end click path
  (PDP → add → checkout) should still be smoke-tested in a browser once, since the
  cart JS can't be exercised headlessly here.

---

## 1.3 Product structured data (JSON-LD)

### Priority
🟠 **P1** (SEO)

### Category
SEO

### Current Situation
The frontend layout has solid basic SEO — `<title>`, meta description, canonical,
Open Graph and Twitter card tags driven by a per-page `$seo` array
(`resources/views/frontend/layouts/frontend.blade.php:22-34`), plus a
`sitemap.xml` and a sensible `robots.txt`. However there is **no JSON-LD
structured data anywhere** (grep for `application/ld+json` / `schema.org` /
`itemprop` returns nothing).

### Problem / Opportunity
Missing `Product` / `Offer` / `AggregateRating`, `BreadcrumbList`, and
`Organization` schema means the store loses rich results (price, availability,
star ratings in Google) — one of the highest-ROI, lowest-risk SEO wins for
e-commerce.

### Proposed Solution
Add a `@stack('head')` JSON-LD block on the product detail page (`Product` +
`Offer` + `AggregateRating` from existing `rating_avg`/`rating_count`), a
`BreadcrumbList` on category/product pages (breadcrumbs already exist), and
`Organization`/`WebSite` on the homepage. All data already exists on the models.

### Business Value
Organic traffic and CTR uplift from rich snippets.

### Technical Impact
Blade only (product show, home). No backend/DB change.

### Complexity
Low

### Dependencies
None.

### Risks
Must keep JSON in sync with visible price/stock (use the same variables).

### Evidence
No `ld+json` in `resources/views/frontend/**`; SEO meta present in layout.

### Confidence
High.

### Recommendation
**Implement Soon.**

### Status — ✅ Implemented · 2026-08-13
Product detail pages now emit `Product` + `Offer` (base-currency price, availability)
+ `AggregateRating` (from `rating_avg`/`rating_count`, only when reviews exist) and a
`BreadcrumbList`, via `@push('head')` JSON-LD. The homepage emits `Organization` and
`WebSite` with a `SearchAction` pointing at the new search. Covered by
`StorefrontEnhancementsTest`.

---

## 1.4 Guest checkout

### Priority
🟡 **P2** (conversion — business decision)

### Category
Conversion / Checkout

### Current Situation
Checkout is **login-required** — `/checkout`, `/checkout` POST, and confirmation
sit inside `Route::middleware('auth')` (`routes/web.php:76-85`). Every order is
tied to a `user_id`. A comment explicitly states this is intentional.

### Problem / Opportunity
Forced account creation is one of the most-cited causes of cart abandonment.
This is a **deliberate design choice**, not a bug — so it is a business trade-off,
not a defect.

### Proposed Solution
Optional: allow "checkout as guest" that creates a lightweight/lazy account from
the email at order time (many Laravel shops do this). Keep wallet/loyalty behind
real accounts.

### Business Value
Potential checkout-completion uplift.

### Technical Impact
Auth flow, `CheckoutService` (already stores customer fields on the order),
account linking.

### Complexity
Medium

### Dependencies
Wallet & account features assume a `user_id`.

### Risks
Fraud/duplication of accounts; complicates order history linking.

### Evidence
`routes/web.php:76`; `CheckoutService::placeOrder` uses `Auth::id()`.

### Confidence
High (that it is required); Medium (that removing it helps *this* store — depends
on the market).

### Recommendation
**Consider.** Validate against the actual abandonment data before building.

### Status — ✅ Implemented · 2026-08-13
Enabled. The checkout controller was already guest-aware (`index()` hides wallet
for guests, `prefill()` returns `[]`, `store()` uses the form email + session
`pending_order_id`, `confirmation()` reads the session); only the route `auth`
middleware gated it. Checkout + PayWay pay/success/cancel routes are now public,
guarded per-order by session/account ownership (`PaymentController::authorizeOrder`)
and the gateway result is still re-verified server-side before an order is marked
paid. Guest orders are created with `user_id = null`. Covered by tests (guest
reaches checkout; guest order placed with null user). **Follow-up (nice-to-have):**
a guest order-lookup page (order number + email), since guests can't use
`/account/orders`.

---

## 1.5 Per-customer coupon usage limit

### Priority
🟡 **P2**

### Category
Promotions / Integrity

### Current Situation
Coupons enforce a **global** `usage_limit` vs `used_count`
(`Coupon::scopeActive`, `app/Models/Coupon.php:41`), min-spend, max-discount, and
date window — all validated server-side at checkout. There is **no per-customer
usage cap** and the redemption is a global `increment('used_count')`
(`CheckoutService.php:262`).

### Problem / Opportunity
A single customer can reuse the same coupon on every order up to the global cap.
Also the check-then-increment is not transactionally reserved, so a small race
near the cap could allow slight over-redemption under concurrency.

### Proposed Solution
Add a `coupon_redemptions` table (`coupon_id`, `user_id`, `order_id`) and enforce
a `per_user_limit`; reserve/lock the coupon row inside the checkout transaction.

### Business Value
Protects margin on promotions; prevents abuse.

### Technical Impact
New table + `CheckoutService` + coupon admin form field.

### Complexity
Medium

### Dependencies
Coupon admin CRUD.

### Risks
Low.

### Evidence
`Coupon.php:41-48`; `CheckoutService.php:226-262`.

### Confidence
High.

### Recommendation
**Consider.**

### Status — ✅ Implemented · 2026-08-13
Added `coupons.per_user_limit` (migration `2026_08_13_000003`) + admin form field +
request validation. `Coupon::reachedPerUserLimit($userId)` counts the customer's
prior orders on the coupon; `CheckoutService::validateCoupon()` reflects it in the
preview and `placeOrder()` rejects an over-limit redemption inside the transaction.
Guests (null user) and coupons without a per-user cap are unaffected. Covered by
tests. (A dedicated `coupon_redemptions` table with row-level reservation remains a
possible future hardening for high-concurrency abuse, but the order-count check
covers the normal case.)

---

# 2. UX/UI Improvements

| # | Current UI | Problem | Recommended | User benefit | Complexity | Priority |
|---|---|---|---|---|---|---|
| 2.1 | Storefront pages use large **inline `style="…"` blocks** in Blade | Inconsistent spacing/colors, hard to theme, no reuse | Move recurring storefront styles into reusable classes | Consistent premium feel, easier maintenance | Medium | 🟡 **Started 2026-08-13** — extracted the repeated filter-section heading into `.ut-filter-heading` (shop page) as a safe, visually-identical slice. **Full migration deferred by design:** a blind mass find-replace across dozens of files risks the polished UI and needs an asset build + visual QA — do it incrementally per page with a browser check. |
| 2.2 | PDP gallery has a **zoom icon** button but zoom behavior not confirmed | ⚠️ Unable to verify the icon actually triggers image zoom | Ensure click-to-zoom / lightbox works on desktop + touch | Trust, closer inspection of product | Low | 🟡 P2 |
| 2.3 | Search is a client-side DOM filter | No "no results" recovery, no suggestions | Server search + empty-state with suggestions (ties to §1.1) | Product discovery | Medium | 🟠 P1 |
| 2.4 | Reviews rely on app-level `hasReviewed` dedupe only | No DB unique constraint on `(user_id, product_id)` | Add a unique index to harden against double-submit races | Data integrity | Low | ✅ **Done 2026-08-13** (migration `2026_08_13_000002`, dedupes then adds `reviews_user_product_unique`; guests/null user_id unconstrained) |

---

# 3. Conversion Optimization

- **Free-shipping progress hint** — ✅ **Done 2026-08-13.** The cart drawer already
  had a nudge but on a hardcoded `$75`; it is now driven by the real configured
  threshold — `CheckoutService::freeShippingThreshold()` (lowest active
  `free_over_amount`) → `window.UT_SHIP_FREE` → `main.js` (falls back to 75 when
  nothing is configured).
- **Cross-sell is already built** (`ProductService::crossSell`) — verify it is
  surfaced in the cart drawer and PDP; if not wired to the UI, wire it. 🟡 P2.
- **Abandoned-cart recovery** — ✅ **Done 2026-08-13.** Added
  `AbandonedCartReminderMail` (queued) + `emails/abandoned/reminder` markdown view +
  `AbandonedCartService::sendReminders()` (idempotent via a new `reminder_sent_at`
  column, migration `2026_08_13_000004`; emails carts idle 1h–7d, marks them
  contacted) + `shop:send-abandoned-cart-reminders` command scheduled hourly in
  `routes/console.php`. Covered by tests (sends once, idempotent, skips fresh carts).
  Requires the OS cron to run `php artisan schedule:run`.
- **Back-in-stock notifications** — not found. Good fit given stock is tracked.
  🔵 P3.

---

# 4. Customer Experience Improvements

- **Order tracking, PDF invoice, notifications, wishlist, wallet, addresses,
  verified reviews** — already implemented and solid. *Keep.*
- **Guest → account cart merge** — ✅ **Verified correct 2026-08-13. Keep.** Although
  `CartService::sync` replaces the server cart, the client (`public/assets/frontend/js/main.js`,
  `mergeCartLines` + `initCart`) performs a **union merge** of the localStorage cart
  and the account's saved cart (max qty per line key) *before* syncing, then adopts
  the server's re-priced result. Guest carts are not lost. No change needed.
- **Recently viewed** — implemented (`RecentlyViewedService`). *Keep.*

---

# 5. Admin & Business Improvements

The admin surface is already deep. Incremental ideas only:
- **Low-stock / reorder alerts** — reorder rules + purchase orders exist
  (`InventoryController::reorder`). Consider surfacing alerts on the dashboard /
  admin notifications. 🟡 P2.
- **Coupon per-user limit field** (ties to §1.5). 🟡 P2.
- **Stale `CLAUDE.md`** — update it: the storefront is fully built, not "view-only
  stubs." This misleads future contributors. 🟠 P1 (docs), trivial.

---

# 6. Technical Improvements

- **Indexes for storefront filters** — the schema already has 78 `index()` and 59
  FKs. When §1.1 lands, confirm indexes exist on `products.status`,
  `category_id`, `brand_id`, `sort_order`, and any price column used for range
  filters. 🟠 P1, Low.
- **`ShopController::show` slug fallback** loads **all** active products and
  iterates in PHP when both the slug column lookup and numeric-ID lookup miss
  (`ShopController.php:78-85`). Rare in practice (slug lookup is first), but on a
  large catalog this final branch is a full scan — bound it or drop it once slugs
  are guaranteed. 🟡 P2, Low.
- **Coupon redemption atomicity** — reserve within the transaction (ties to §1.5).
  🟡 P2.
- **Sitemap URL in robots.txt** — ✅ **Done 2026-08-13** (see the dynamic-route note
  above; `Sitemap:` is now absolute).
- **Queued mail** — order confirmation is queued (`ShouldQueue`) — good. Ensure
  the queue worker is supervised in production (`docs/supervisor/` exists). Keep.
- **Header search → AJAX live search** — ✅ **done 2026-08-13.** (Note: the earlier
  claim that the header "loads all products" was wrong — `NavigationService` is
  cached + capped. The real gap was that the search modal had no functional
  search; it now fetches `GET /shop-search` and renders live results.)
- **`robots.txt` sitemap URL** — ✅ **done 2026-08-13.** `robots.txt` is now a
  dynamic route emitting an absolute `Sitemap:` URL via `url('/sitemap.xml')`
  (static `public/robots.txt` removed so the route serves).

---

# 7. Advanced / Future Ideas

- Personalized recommendations / smarter search (only worthwhile after §1.1).
- Loyalty tiers / referral / gift cards / store credit — the **wallet + wallet
  transactions** infrastructure already exists and is a strong foundation if the
  business wants loyalty. 🔵 P3.
- Subscription products — no current evidence of demand. **Do Not Implement**
  without a business driver.

---

# 8. Recommended Roadmap

## Phase 1 — Critical / Correctness
- §1.1 Server-side listing, search & pagination
- §1.2 Variant match by ID (oversell/mispricing fix)

## Phase 2 — High Value UX / SEO / Conversion
- §1.3 JSON-LD structured data
- §2.3 Server search empty-state, §3 free-shipping nudge
- §4 confirm cart merge, §3 confirm abandoned-cart email
- §5 update `CLAUDE.md`

## Phase 3 — Growth
- §1.4 Guest checkout (if data supports it)
- §1.5 Per-customer coupon limits
- Low-stock dashboard alerts

## Phase 4 — Advanced / Scale
- Loyalty on the wallet base, recommendations, back-in-stock, FTS search index

---

# 9. Top Recommended Improvements

| Priority | Feature / Improvement | Business Value | UX Value | Complexity | Recommendation |
|---|---|---|---|---|---|
| 🔴 P0 | Server-side listing/search/pagination (§1.1) | High | High | Medium | Implement Soon |
| 🟠 P1 | Variant match by ID (§1.2) | High (integrity) | Med | Medium | Implement Soon |
| 🟠 P1 | JSON-LD structured data (§1.3) | High (SEO) | Low | Low | Implement Soon |
| 🟠 P1 | Confirm cart merge on login (§4) | Med | High | Low | Implement Soon |
| 🟠 P1 | Confirm abandoned-cart email sends (§3) | High | Med | Low | Verify |
| 🟡 P2 | Per-customer coupon limit (§1.5) | Med | Low | Medium | Consider |
| 🟡 P2 | Free-shipping nudge (§3) | Med | Med | Low | Consider |
| 🟡 P2 | Move inline styles into design system (§2.1) | Low | Med | Medium | Consider |
| 🟡 P2 | Guest checkout (§1.4) | Med | High | Medium | Consider (data-driven) |

---

# 10. Ideas Rejected

| Feature | Why considered | Why rejected |
|---|---|---|
| Full SPA / React rewrite of storefront | Prompt lists React expertise | The Blade + Alpine stack is coherent and working; a rewrite is high-risk with no business driver. **Do Not Implement.** |
| Subscription products | Common in e-commerce | No evidence of demand or catalog fit. **Do Not Implement** without a driver. |
| Third-party search (Algolia/Meilisearch) now | Scales search | Premature — `LIKE`/FTS5 covers the current catalog size; revisit at scale. |
| Replacing ABA PayWay integration | "Modernize payments" | The integration is correct and server-verified; no reason to touch it. **Keep.** |

---

# 11. Final Recommendation

## Must Implement
- §1.1 Server-side listing/search/pagination
- §1.2 Variant-by-ID matching

## Should Consider
- §1.3 JSON-LD, §4 cart-merge check, §3 abandoned-cart email verification,
  free-shipping nudge, `CLAUDE.md` update

## Future
- Guest checkout, per-customer coupon limits, loyalty on wallet, FTS/recommendations

## Do Not Implement
- Storefront SPA rewrite, subscription products, payment gateway replacement

---

# 12. Implementation Status

- [x] §1.1 Server-side product listing, search & pagination — **Completed** 2026-08-13 (shop page + homepage bounded pool + header live-search)
- [x] Header global-search → AJAX live search endpoint — **Completed** 2026-08-13
- [x] §1.2 Variant match by ID — **Completed** 2026-08-13 (server + client plumbing; browser smoke-test recommended)
- [x] §1.3 Product/Breadcrumb/Organization JSON-LD — **Completed** 2026-08-13
- [x] §1.4 Guest checkout — **Completed** 2026-08-13 (guest order-lookup page is a nice-to-have follow-up)
- [x] §1.5 Per-customer coupon usage limit — **Completed** 2026-08-13
- [~] §2.1 Inline styles → design system — **Started** 2026-08-13 (representative slice done; full migration deferred — needs build + visual QA)
- [x] §2.4 Unique index on reviews (user_id, product_id) — **Completed** 2026-08-13
- [x] §3 Free-shipping nudge (data-driven threshold) — **Completed** 2026-08-13
- [x] §3 Abandoned-cart recovery email — **Completed** 2026-08-13
- [x] §4 Guest→login cart merge — **Verified correct** 2026-08-13 (already a union merge; no change)
- [x] §5 Update stale `CLAUDE.md` storefront description — **Completed** 2026-08-13
- [x] §6 Absolute sitemap URL in robots.txt — **Completed** 2026-08-13

### Test coverage
`tests/Feature/ShopListingTest.php`, `tests/Feature/StorefrontEnhancementsTest.php`,
and `tests/Feature/StorefrontBatch2Test.php` cover server-side listing/search/
pagination, AJAX search, JSON-LD, dynamic robots.txt, the home bounded pool,
variant-by-id checkout + cart round-trip (incl. forged-id rejection), per-user
coupon limit, abandoned-cart reminders (send-once/idempotent/skip-fresh), and guest
checkout. **Full suite: 140 passing.**

### Remaining (intentionally not auto-done)
- **§2.1 full design-system migration** — a page-by-page cosmetic refactor that
  needs an asset build + visual QA; doing it blind risks the polished UI. Started
  with a safe representative slice.
- **Nice-to-have follow-ups noted inline:** guest order-lookup page (§1.4), a
  `coupon_redemptions` table for high-concurrency coupon abuse (§1.5), and a
  browser smoke-test of the PDP→cart→checkout variant path (§1.2).
- **Still genuinely proposed (not requested to build):** §1.5-style advanced items,
  personalization/loyalty (§7), etc.
