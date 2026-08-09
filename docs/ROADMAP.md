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
- ✅ **Attributes** (generic EAV: Size/Color/Custom, values linked to Size/Color masters
  with live sync) — powers the product variant builder (ERP matrix + generate)
- ✅ Categories · Sizes · Colors · **Brands**
- ✅ **Coupons** (code, %/fixed, min-spend, cap, usage limit, validity window)
- ✅ Users · Roles · Permissions — **Policy-based authorization** (per-resource
  policies + granular spatie permissions; see `docs/ADMIN-CRUD-GUIDELINE.md`)
- ✅ Settings · Profile
- ✅ **Orders** (KPI list + filters, pro detail + status/payment + activity log, printable invoice/packing slip)
- ✅ **Inventory** (stock adjustments with reason + per-variant movement history) · **product import/export** (Excel)
- ✅ **Real dashboard** (ApexCharts revenue/status, KPIs, feeds, period selector)
- ✅ **Shipping methods** · **Tax rules**
- ✅ **Merchandising**: Banners · Collections · Announcement bar
- ✅ **Content**: CMS Pages · FAQ manager
- ✅ Admin UI kit: sectioned + collapsible sidebar, refined 2026 data tables (search / per-page /
  pagination / bulk actions), table loader, custom select, image + gallery upload, product picker,
  form modal, delete modal

## ✅ Frontend (storefront — now fully functional)
- ✅ Shop list/detail (gallery, variant picker, stock state, specs, related, reviews),
  Cart (DB + guest merge), **Checkout → order → payment**, Account
  (dashboard/orders/wishlist/addresses/wallet/notifications), Auth
  (login/register/forgot/reset/OTP + Google OAuth), info pages, storefront search.
- ✅ Real-time order notifications (Reverb), SEO meta on shop/product, **XML sitemap**.
- ⬜ Remaining: wire admin-managed home content (banners/collections/announcement)
  fully into the home page rails config.

---

# PHASE 1 — Finish the Admin panel  `[A]` (do this first)

### 1.1 Catalog polish
- ⬜ `[A]` Generalize the Brand modal into a shared `<x-form-modal>` component *(optional polish)*
- ⬜ `[A]` Apply modal create/edit to **Sizes** and **Colors** *(optional polish)*
- ✅ `[A]` Guard **delete** when records are in use (Brand/Category/Size/Color referenced by products/variants)
- ✅ `[A]` Bulk actions on tables (bulk delete / enable / disable) — across catalog + orders + products

### 1.2 Coupons & Discounts
- ✅ `[B]` `coupons` migration + `Coupon` model (code, type %/fixed, value, min_spend, starts_at, expires_at, usage_limit, used_count, status)
- ✅ `[A]` Coupon admin CRUD (list, create/edit, toggle, delete)
- ✅ `[B]` Coupon domain logic (`isValid()`, `discountFor()`, scopes) — *not yet wired into checkout*

### 1.3 Orders (admin side of the data model)  ✅
- ✅ `[B]` Migrations + models: `Order`, `OrderDetail`, `orders`↔`users`, price snapshots
- ✅ `[B]` Order status enum + state machine (pending → paid → processing → shipped → delivered → cancelled/refunded); + `PaymentStatus`
- ✅ `[A]` Admin order list (KPI stat bar; filters: status, date-range, customer, price) + pro detail view (stepper, activity log)
- ✅ `[A]` Update order status/payment, add tracking number, internal notes — with `order_events` activity log
- ✅ `[A]` Invoice / packing slip (printable)

### 1.4 Inventory  ✅
- ✅ `[A]` Low-stock dashboard widget + filter (Inventory index + dashboard)
- ✅ `[B]` Stock adjustment log (manual +/- with reason) — `stock_movements` + `StockService`
- ✅ `[A]` Stock movement history per variant

### 1.5 Real Dashboard  ✅
- ✅ `[A]` KPI cards: revenue, orders, customers, products (+ low-stock panel), with count-up + trend
- ✅ `[A]` Charts (ApexCharts): revenue area over time, orders-by-status donut, period selector (7d/30d/12m)
- ✅ `[A]` Recent orders feed + low-stock feed

### 1.6 Content & config (admin)
- ✅ `[B]` `Address` model + migration (customer address book, used by checkout)
- ✅ `[A]` Shipping methods & rates (flat / free / free-over-threshold) — `ShippingMethod` + `costFor()`
- ✅ `[A]` Tax rules (rate, inclusive/exclusive)
- ✅ `[A]` CMS pages (about/privacy/terms) backed by DB + **FAQ manager** (Q&A by category)
- ✅ `[A]` Email / notification settings (Settings → **Notifications** tab: order-email
  toggle, sender name/address, admin order-alert copy) — drives `OrderConfirmationMail`

### 1.7 Content & merchandising (discovered from the storefront review)
The **entire home page is hardcoded** today — a shop owner can't change it. Needs admin.
_Admin management is built; rendering these on the storefront home is a Phase 2 task._
- ✅ `[A]` **Banners / hero slides** — image, kicker, title, copy, CTA text + link, sort, active
- ✅ `[A]` **Collections** — curated groups (name, image, linked products) with searchable product picker
- ⬜ `[A]` **Homepage sections** config — which product rails show (best sellers / new / trending / flash sale + countdown)
- ✅ `[A]` **Announcement bar** messages
- ✅ `[B]` **Newsletter subscribers** — capture signups + admin list/export
- ✅ `[A]` Payment method toggles (online / manual QR / wallet) in Settings → Payment

### 1.8 Reviews  ✅
- ✅ `[B]` `Review` model (product, user, rating, title, body, status, verified-purchase)
- ✅ `[A]` Admin **moderation** (approve/reject), product **rating aggregation** (avg + count)
- ✅ `[F]` storefront submit-review (delivered orders) + display

---

# PHASE 2 — Storefront foundation  `[F]`  ✅

### 2.1 Customer auth  ✅
- ✅ `[F]` Wire `AuthController` POST: register, login, logout (+ Google OAuth)
- ✅ `[F]` Forgot / reset password flow
- ✅ `[F]` OTP / email verification

### 2.2 Shop browsing  ✅
- ✅ `[F]` Product listing: filters (category/brand/size/color/price), sort, pagination
- ✅ `[F]` Product detail: gallery, variant picker, stock state, specs, related products
- ✅ `[F]` Storefront search

### 2.3 Cart  ✅
- ✅ `[B]` `carts` + `cart_items` tables; `CartService` (add/update/remove/clear/totals)
- ✅ `[F]` Guest cart (localStorage) + logged-in cart (DB) with merge-on-login
- ✅ `[F]` Add-to-cart with variant + qty; stock validation
- ✅ `[F]` Cart page (line items, qty steppers, remove, subtotal)
- ✅ `[F]` Header cart badge (live count)

---

# PHASE 3 — Checkout & Payment  `[F]` / `[B]`  ✅

- ✅ `[F]` Checkout: address → shipping → payment → review (multi-step)
- ✅ `[F]` Address book (create/select) at checkout; **login required (no guest checkout)** + phone capture
- ✅ `[B]` Place order: create Order+OrderDetails, snapshot prices, **concurrency-safe** stock decrement, apply coupon/shipping/tax
- ✅ `[F]` Payment gateway (ABA PayWay, HMAC-signed) + in-house **Wallet** + manual QR
- ✅ `[B]` Payment callback → order status; idempotent confirm
- ✅ `[F]` Order confirmation page + **confirmation email w/ PDF invoice** + customer invoice download

---

# PHASE 4 — Customer account  `[F]`  ✅

- ✅ `[F]` Orders list + detail + tracking (real data) + invoice PDF download
- ✅ `[F]` Address book CRUD
- ✅ `[B]` `Wishlist` model → ✅ `[F]` wishlist add/remove, move to cart
- ✅ `[F]` Profile & password update
- ✅ `[B]` `Review` model → ✅ `[F]` submit review (delivered orders) → ✅ `[A]` moderation + product rating display
- ✅ `[F]` **Wallet** (balance, top-up via gateway, transaction ledger)

---

# PHASE 5 — Production hardening

- ✅ `[F]` SEO: meta tags, OG/Twitter cards, **XML sitemap**, canonical, robots.txt
- ✅ `[B]` Notifications: order emails (customer + admin copy), admin DB notifications page + Reverb
- 🔜 `[B]` i18n / multi-currency: locale switch + translatable content exist; **currency config
  (symbol/code/position) + `money()` helper + `window.UT_CURRENCY` done**. Remaining: FX conversion
  rates + per-currency switching + migrating remaining hardcoded `$` price outputs in views to `money()`
- ✅ `[B]` Tests (Pest): cart, checkout, order lifecycle, coupons, wallet, email, sitemap, currency (115 passing)
- ✅ `[B]` Performance: orders lookup indexes; **cached settings map (1 query/request) + 10-min nav cache**. Remaining: image optimization audit
- ✅ `[B]` Security: rate limiting (auth/checkout/coupon/newsletter) + **baseline security headers middleware** (nosniff, frame, referrer, permissions, HSTS on https)

---

## Remaining work (Phases 1–4 essentially complete)
Admin panel, storefront, cart, checkout, payment, wallet, account, reviews, and
notifications are all built and tested (109 Pest tests passing). What's left:

1. ✅ **1.7 Homepage sections config** — admin toggles which product rails (best / flash /
   new / trending) show + their headings (Settings → Home); banners/collections/announcements
   already render on the home page.
2. 🔜 **Multi-currency (full)** — FX rates, per-currency switching, migrate remaining
   hardcoded `$` price outputs in views to the `money()` helper. Currency config foundation done.
3. 🔜 **Performance** — image optimization audit (caching + indexes done).
4. 🔜 **Security review** — full pass / authz spot-checks (rate limiting + headers done).
5. *(optional polish)* shared `<x-form-modal>` + modal for Sizes/Colors.

> Critical path **done**: Cart → Checkout → Order → Payment all work end to end.
