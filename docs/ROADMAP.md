# 🛒 E-commerce System — Build Roadmap

Task plan for completing the shop management system. Tasks are tagged by layer so
the **admin/backend** can be finished before touching the **storefront/frontend**.

**Legend**
- `[A]` Admin / backend panel only — no storefront changes
- `[F]` Frontend / storefront (customer-facing)
- `[B]` Shared backend (models, migrations, services used by both)
- ✅ done · 🔜 next · ⬜ todo

---

## ✅ Already built (admin)
- ✅ Products (variants, images, specifications, tags)
- ✅ Categories · Sizes · Colors · **Brands**
- ✅ Users · Roles · Permissions (RBAC)
- ✅ Settings · Profile
- ✅ Admin UI kit: sectioned sidebar, data tables (search / per-page / pagination),
  table loader, custom select, image upload, form modal, delete modal

## ✅ Frontend (view-only stubs — no logic yet)
- Shop list/detail, Cart, Checkout, Account (dashboard/orders/wishlist/addresses),
  Auth (login/register/forgot/otp), info pages. **All render static Blade only.**

---

# PHASE 1 — Finish the Admin panel  `[A]` (do this first)

### 1.1 Catalog polish
- ⬜ `[A]` Generalize the Brand modal into a shared `<x-form-modal>` component
- ⬜ `[A]` Apply modal create/edit to **Sizes** and **Colors**
- ⬜ `[A]` Guard **delete** when records are in use (Brand/Category/Size/Color referenced by products/variants)
- ⬜ `[A]` Bulk actions on tables (bulk delete / enable / disable)

### 1.2 Coupons & Discounts
- ⬜ `[B]` `coupons` migration + `Coupon` model (code, type %/fixed, value, min_spend, starts_at, expires_at, usage_limit, used_count, status)
- ⬜ `[A]` Coupon admin CRUD (list, create/edit modal, toggle, delete)
- ⬜ `[B]` Coupon validation service (validity, expiry, min-spend, usage limit)

### 1.3 Orders (admin side of the data model)
- ⬜ `[B]` Migrations + models: `Order`, `OrderItem`, `orders`↔`users`, price snapshots
- ⬜ `[B]` Order status enum + state machine (pending → paid → processing → shipped → delivered → cancelled/refunded)
- ⬜ `[A]` Admin order list (filters: status, date, customer) + detail view
- ⬜ `[A]` Update order status, add tracking number, internal notes
- ⬜ `[A]` Invoice / packing slip (printable)

### 1.4 Inventory
- ⬜ `[A]` Low-stock dashboard widget + filter
- ⬜ `[B]` Stock adjustment log (manual +/- with reason)
- ⬜ `[A]` Stock movement history per variant

### 1.5 Real Dashboard
- ⬜ `[A]` KPI cards: revenue, orders, customers, products, low-stock
- ⬜ `[A]` Charts: sales over time, top products, orders by status
- ⬜ `[A]` Recent orders / recent signups feeds

### 1.6 Content & config (admin)
- ⬜ `[B]` `Address` model + migration (shared, used by checkout later)
- ⬜ `[A]` Shipping methods & rates (zones, flat/weight based)
- ⬜ `[A]` Tax rules (rate, inclusive/exclusive)
- ⬜ `[A]` CMS pages (about/faq/privacy/terms) backed by DB
- ⬜ `[A]` Email templates / notification settings

---

# PHASE 2 — Storefront foundation  `[F]` (start here after admin is done)

### 2.1 Customer auth (currently GET stubs only)
- ⬜ `[F]` Wire `AuthController` POST: register, login, logout
- ⬜ `[F]` Forgot / reset password flow
- ⬜ `[F]` OTP / email verification (if kept)

### 2.2 Shop browsing
- ⬜ `[F]` Product listing: filters (category/brand/size/color/price), sort, pagination
- ⬜ `[F]` Product detail: gallery, variant picker, stock state, specs, related products
- ⬜ `[F]` Storefront search

### 2.3 Cart
- ⬜ `[B]` `carts` + `cart_items` tables; `CartService` (add/update/remove/clear/totals)
- ⬜ `[F]` Guest cart (session) + logged-in cart (DB) with merge-on-login
- ⬜ `[F]` Add-to-cart with variant + qty; stock validation
- ⬜ `[F]` Cart page (line items, qty steppers, remove, subtotal)
- ⬜ `[F]` Header cart badge (live count)

---

# PHASE 3 — Checkout & Payment  `[F]` / `[B]`

- ⬜ `[F]` Checkout: address → shipping → payment → review
- ⬜ `[F]` Address book (create/select) at checkout + guest checkout
- ⬜ `[B]` Place order: create Order+OrderItems, snapshot prices, decrement stock, apply coupon/shipping/tax
- ⬜ `[F]` Payment gateway (Stripe / PayPal / local e.g. ABA/Bakong) + COD
- ⬜ `[B]` Payment webhooks → order status; failure/retry handling
- ⬜ `[F]` Order confirmation page + confirmation email

---

# PHASE 4 — Customer account  `[F]`

- ⬜ `[F]` Orders list + detail + tracking (real data)
- ⬜ `[F]` Address book CRUD
- ⬜ `[B]` `Wishlist` model → ⬜ `[F]` wishlist add/remove, move to cart
- ⬜ `[F]` Profile & password update
- ⬜ `[B]` `Review` model → ⬜ `[F]` submit review (delivered orders) → ⬜ `[A]` moderation + product rating display

---

# PHASE 5 — Production hardening

- ⬜ `[F]` SEO: meta tags, OG, sitemap, canonical
- ⬜ `[B]` Notifications: order emails (customer + admin), DB notifications page
- ⬜ `[B]` i18n / multi-currency (locale switch already exists)
- ⬜ `[B]` Tests (Pest): cart, checkout, order lifecycle, coupons
- ⬜ `[B]` Performance: eager loading, query/index review, caching, image optimization
- ⬜ `[B]` Security review + rate limiting on auth/checkout

---

## Suggested order
1. **Phase 1** admin items — start with **1.1 polish** or **1.2 Coupons** (fully standalone).
2. **1.3 Orders data model + admin** (no storefront needed; seed test orders).
3. **1.4 Inventory** + **1.5 Dashboard** (dashboard is nicer once orders exist).
4. Only then move to **Phase 2** storefront.

> Critical path to a working shop (once frontend starts): **Cart → Checkout → Order → Payment.**
