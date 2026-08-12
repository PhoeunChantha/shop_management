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

---

# 2. UX/UI Improvements

| # | Current UI | Problem | Recommended | User benefit | Complexity | Priority |
|---|---|---|---|---|---|---|
| 2.1 | Storefront pages use large **inline `style="…"` blocks** and inline `<style>` in Blade (shop/index, shop/show) | Design tokens are declared in `resources/css/app.css`, but storefront pages bypass them — inconsistent spacing/colors, hard to theme, no reuse | Move recurring storefront styles into component classes / the design system; keep tokens as the single source | Consistent premium feel, easier maintenance | Medium | 🟡 P2 |
| 2.2 | PDP gallery has a **zoom icon** button but zoom behavior not confirmed | ⚠️ Unable to verify the icon actually triggers image zoom | Ensure click-to-zoom / lightbox works on desktop + touch | Trust, closer inspection of product | Low | 🟡 P2 |
| 2.3 | Search is a client-side DOM filter | No "no results" recovery, no suggestions | Server search + empty-state with suggestions (ties to §1.1) | Product discovery | Medium | 🟠 P1 |
| 2.4 | Reviews rely on app-level `hasReviewed` dedupe only | No DB unique constraint on `(user_id, product_id)` (reviews migration only indexes `status` and `(product_id,status)`) | Add a unique index to harden against double-submit races | Data integrity | Low | 🟡 P2 |

---

# 3. Conversion Optimization

- **Free-shipping progress hint** — shipping methods already support
  `free_over_amount` (`CheckoutService::shippingMethods`). Surface a "Add $X for
  free shipping" nudge in cart/drawer. 🟠 P1, Low.
- **Cross-sell is already built** (`ProductService::crossSell`) — verify it is
  surfaced in the cart drawer and PDP; if not wired to the UI, wire it. 🟡 P2.
- **Abandoned-cart recovery** — the data + admin screen exist
  (`AbandonedCartController`, `AbandonedCartService`). ⚠️ Verify whether a
  recovery **email job** actually sends; if only captured, add the send. 🟠 P1.
- **Back-in-stock notifications** — not found. Good fit given stock is tracked.
  🔵 P3.

---

# 4. Customer Experience Improvements

- **Order tracking, PDF invoice, notifications, wishlist, wallet, addresses,
  verified reviews** — already implemented and solid. *Keep.*
- **Guest → account cart merge** — ⚠️ `CartService::sync` **replaces** the server
  cart with the client lines (`CartService.php:44`). Confirm that on login the
  guest localStorage cart is *merged* (not lost/overwritten). If it overwrites,
  add a union merge. 🟠 P1, Low.
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
- **Sitemap URL in robots.txt is relative** (`Sitemap: /sitemap.xml`) — search
  engines expect an absolute URL. 🟡 P2, trivial.
- **Queued mail** — order confirmation is queued (`ShouldQueue`) — good. Ensure
  the queue worker is supervised in production (`docs/supervisor/` exists). Keep.

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

- [ ] §1.1 Server-side product listing, search & pagination — **Proposed**
- [ ] §1.2 Variant match by ID — **Proposed**
- [ ] §1.3 Product JSON-LD structured data — **Proposed**
- [ ] §1.4 Guest checkout — **Proposed**
- [ ] §1.5 Per-customer coupon usage limit — **Proposed**
- [ ] §2.1 Inline styles → design system — **Proposed**
- [ ] §2.4 Unique index on reviews (user_id, product_id) — **Proposed**
- [ ] §3 Free-shipping nudge — **Proposed**
- [ ] §3 Verify abandoned-cart recovery email — **Proposed**
- [ ] §4 Verify guest→login cart merge — **Proposed**
- [ ] §5 Update stale `CLAUDE.md` storefront description — **Proposed**
- [ ] §6 Relative sitemap URL in robots.txt — **Proposed**

_No feature is marked completed; nothing was implemented during this audit._
