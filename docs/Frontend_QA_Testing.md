# Frontend QA Testing Checklist

Manual QA checklist for the **storefront** (customer-facing surface). Covers every
public and account page, the cart → checkout → payment flow, auth (including Google
sign-in), and cross-cutting concerns (i18n, currency, responsive, SEO).

- **Scope:** `App\Http\Controllers\Frontend\*`, routes named `frontend.*` in
  `routes/web.php`, views under `resources/views/frontend/**`.
- **How to use:** copy the relevant section into a test run, mark each row
  Pass / Fail / N/A, and file failures with the browser, viewport, and steps.
- **Test data:** at least one product with variants (size/color) and stock, one
  out-of-stock product, one active coupon, one customer account (email/password),
  and one Google account for OAuth.

Legend: ☐ = to test.

---

## 0. Environment & setup

- ☐ `composer dev` (or `php artisan serve` + `npm run dev`) is running.
- ☐ `npm run dev`/`npm run build` has run — CSS/JS changes are compiled.
- ☐ Database seeded (`php artisan migrate --seed`) with products, categories, at
  least one coupon.
- ☐ Storefront reachable at the dev URL (e.g. `http://127.0.0.1:8000`).
- ☐ `APP_URL` matches the host you test on (mismatches break `asset()` image URLs).
- ☐ For Google sign-in: `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` /
  `GOOGLE_REDIRECT_URI` set, and `google_login` setting enabled.

---

## 1. Home page (`frontend.home` — `/`)

- ☐ Page loads without console errors.
- ☐ Announcement bar scrolls/marquees; messages readable.
- ☐ Header logo links home; site name/logo reflect current settings.
- ☐ Category mega-menus open on hover and list sub-categories with working links.
- ☐ Hero, featured collections, product sections render real data (no placeholders).
- ☐ Testimonials / social proof render.
- ☐ Newsletter signup (`frontend.newsletter.subscribe`): valid email succeeds,
  invalid email shows error, throttle (5/min) enforced after repeated submits.
- ☐ Footer links (about, contact, faq, privacy, terms, social) all resolve.

## 2. Header / global navigation (all pages)

- ☐ Search button opens the search overlay (⌘K / Ctrl+K if wired).
- ☐ Search overlay: typing debounces and returns product results with image, name,
  price; "no products match" shown for gibberish; result links open product page.
- ☐ Language switcher (EN/KH) changes locale and persists across pages.
- ☐ Currency switcher (if >1 display currency) changes prices site-wide.
- ☐ Account dropdown (guest): shows **Sign in** / **Create account**.
- ☐ Account dropdown (signed in): shows the **user's real name** (not a static
  label) and email tooltip; **My account / Orders & tracking / Saved pieces /
  Sign out** links work.
- ☐ Account button shows the **Google profile photo** when set, else the user icon.
- ☐ Notifications bell (signed in) shows unread count; links to notifications.
- ☐ Wishlist icon shows saved count; cart icon shows item count.
- ☐ Mobile: hamburger opens off-canvas menu; all nav links work; menu closes.

## 3. Shop listing (`frontend.shop.index` — `/shop`)

- ☐ Products list with image, name, price, badges; pagination works and preserves
  filters (`withQueryString`).
- ☐ Search (`?search=`) filters server-side; empty state shown when nothing matches.
- ☐ Category / brand / size / color / price filters apply and combine correctly.
- ☐ Sort options (price, newest, etc.) reorder results.
- ☐ Per-page selector changes count and reloads.
- ☐ Loading overlay shows while a filter/search reloads (forms use `requestSubmit`).
- ☐ Out-of-stock products display a clear indicator.
- ☐ Add-to-wishlist toggle works from the listing (guest → localStorage; signed-in
  → server).

## 4. Product detail (`frontend.shop.show` — `/shop/{product}`)

- ☐ Gallery: main image + thumbnails switch; zoom/lightbox (if present) works.
- ☐ Name, price (with any sale price), short + full description render; translated
  fields respect current locale.
- ☐ Variant selectors (size/color) update price/stock/SKU; unavailable combos
  disabled.
- ☐ Quantity input respects min/max and available stock.
- ☐ **Add to cart** adds the correct variant + qty; cart drawer/badge updates.
- ☐ Out-of-stock variant disables the add-to-cart button.
- ☐ Wishlist toggle reflects saved state.
- ☐ Reviews: list shows verified-buyer reviews with rating; average rating correct;
  pagination if many.
- ☐ Related / recently-viewed products render and link correctly.
- ☐ SEO: page title, meta description, Open Graph, and product structured data
  (JSON-LD) present in source.
- ☐ Invalid/unknown product slug returns 404.

## 5. Cart (`frontend.cart.index` — `/cart`)

- ☐ Guest cart persists in localStorage across reloads.
- ☐ Signed-in cart persists server-side and syncs across devices/sessions
  (`cart.sync`).
- ☐ On login, a guest cart merges into the account cart without losing items.
- ☐ Update quantity re-prices line and totals; remove line updates totals.
- ☐ Line prices are re-priced server-side (not trusted from client).
- ☐ Coupon field (`checkout.coupon`): valid code applies discount; invalid/expired
  shows error; throttle (20/min) enforced.
- ☐ Cart totals (subtotal, discount, shipping estimate, tax, total) are correct.
- ☐ Empty-cart state shows and links back to shop.
- ☐ "Proceed to checkout" navigates to checkout.

## 6. Checkout (`frontend.checkout.*` — `/checkout`)

- ☐ Guest checkout allowed; contact details captured.
- ☐ Signed-in checkout pre-fills saved address/contact.
- ☐ Every line is **re-priced server-side** and stock **row-locked** on submit.
- ☐ Shipping method selection updates totals.
- ☐ Address form validation: required fields, phone/email format.
- ☐ Placing an order with an out-of-stock item is rejected with a clear message.
- ☐ Coupon applied in cart carries into checkout totals.
- ☐ Submit throttle (12/min) enforced.
- ☐ Confirmation page (`checkout.confirmation`) shows order number and summary.
- ☐ Order appears in the customer's **Orders** list afterward.

## 7. Payment — ABA PayWay (`frontend.payment.*`)

- ☐ Choosing online payment redirects to the PayWay `pay` screen.
- ☐ Successful payment returns to `payment.success`; order marked paid **only after
  server-side re-verification**.
- ☐ Cancelled payment returns to `payment.cancel`; order stays unpaid; cart intact.
- ☐ Payment callback (`payment.callback`) updates order status idempotently (no
  double-processing on repeat calls).
- ☐ A guest cannot access another order's payment page (session/ownership guard).
- ☐ Wallet top-up pay/success flow works for signed-in users.

## 8. Authentication (`frontend.login`, `register`, password, OTP)

- ☐ Register: valid data creates a `user`/customer, logs in, redirects sensibly.
- ☐ Register validation: duplicate email, weak password, mismatched confirm.
- ☐ Login: correct credentials succeed; wrong credentials show error; throttle
  (6/min) enforced.
- ☐ "Remember me" keeps the session.
- ☐ Forgot password sends a reset link; reset page updates the password; old
  password no longer works.
- ☐ OTP verify page behaves (if part of the active flow).
- ☐ Logout ends the session and returns to a public page.
- ☐ Unauthenticated access to `/account/*` redirects to `frontend.login` (not the
  admin login).

## 9. Google sign-in (`frontend.social.*`)

- ☐ "Continue with Google" redirects to Google's consent screen.
- ☐ First-time sign-in **creates** the account with name + email from Google and
  assigns the `customer` role.
- ☐ Google **profile photo is downloaded and stored**, and then displayed in the
  header dropdown, account header, and profile card.
- ☐ Existing account (same email) signs in and links, does not duplicate.
- ☐ Cancelling on Google's screen returns to login with a friendly message.
- ☐ Account with no email from Google is handled gracefully.
- ☐ With `google_login` disabled or unconfigured, the button/flow is unavailable
  with a clear message.
- ☐ Name shown after login matches the Google name (not a static placeholder).

## 10. Customer account (`frontend.account.*`)

**Dashboard** (`/account`)
- ☐ Greeting shows the user's first name and avatar (photo or initial fallback).
- ☐ Summary widgets (orders, wallet, points, wishlist) show correct numbers.

**Profile** (`/account/profile`)
- ☐ Avatar shows the Google/uploaded photo, else the initial.
- ☐ First/last name pre-filled; email disabled (read-only) with explanation.
- ☐ Save updates name + phone; validation errors display inline; success message.
- ☐ ("Change photo" upload — verify whether wired; note if it is a placeholder.)

**Password** (`/account/password`)
- ☐ Changing password requires/validates correctly; new password works on relogin.

**Addresses** (`/account/addresses`)
- ☐ Add, edit, delete address; set default; default reflected at checkout.
- ☐ Validation on required address fields.

**Wishlist** (`/account/wishlist`)
- ☐ Saved items list; toggle removes; guest wishlist syncs on login (`wishlist.sync`).
- ☐ Add-to-cart from wishlist works.

**Wallet** (`/account/wallet`)
- ☐ Balance and transaction history correct; top-up initiates payment.

**Orders** (`/account/orders`, `orders.show`, `orders.tracking`, `orders.invoice`)
- ☐ Orders list paginates; statuses correct.
- ☐ Order detail shows lines, totals, address, payment status.
- ☐ Tracking page renders status timeline.
- ☐ Invoice PDF downloads and is well-formed.
- ☐ Review: eligible (delivered, not-yet-reviewed) products can be reviewed;
  duplicate review blocked; review appears on the product page after moderation.

**Notifications** (`/account/notifications`)
- ☐ List renders; mark-one-read and mark-all-read update the unread badge.

## 11. Information pages

- ☐ About, Contact, FAQ, Privacy, Terms all load with real content.
- ☐ Contact form (if any) validates and submits.
- ☐ FAQ accordions expand/collapse.

## 12. SEO & crawlability

- ☐ `/sitemap.xml` returns valid XML with absolute URLs.
- ☐ `/robots.txt` served dynamically; disallows `/admin`, `/account`, `/checkout`,
  `/cart`; `Sitemap:` line is an absolute URL.
- ☐ Each key page has a unique `<title>` and meta description.
- ☐ Product pages emit JSON-LD structured data.
- ☐ Canonical tags present where expected.

## 13. Internationalization & currency

- ☐ Switching to KH translates UI strings (no raw keys leaking).
- ☐ Translated product fields (name, descriptions) show in the active locale, with
  primary-language fallback.
- ☐ Locale persists across navigation and after login.
- ☐ Currency switch reformats all prices consistently (symbol + amount).

## 14. Responsive & cross-browser

- ☐ Mobile (≤480), tablet (~768), desktop (≥1280): layouts hold, no overflow.
- ☐ Off-canvas menu, cart drawer, and search overlay usable on touch.
- ☐ Tap targets adequately sized; sticky header behaves.
- ☐ Verify on Chrome, Firefox, Safari, and Edge (latest).

## 15. Accessibility (baseline)

- ☐ Keyboard-navigable: menus, dropdowns, forms, modals reachable and dismissable.
- ☐ Focus states visible; focus trapped inside open modals/off-canvas.
- ☐ Images have meaningful `alt`; icon-only buttons have `aria-label`.
- ☐ Color contrast meets WCAG AA for text.
- ☐ Form fields have associated labels; errors announced.

## 16. Performance & resilience

- ☐ No 404s for assets (images, CSS, JS) in the network tab.
- ☐ Images lazy-load where appropriate; no oversized payloads.
- ☐ No unhandled JS console errors during a full guest → buy → account journey.
- ☐ Slow-network: loading states appear; no duplicate submits on double-click.
- ☐ 404 page renders for unknown URLs; 500s do not leak stack traces in production.

## 17. Security spot-checks

- ☐ All state-changing forms include CSRF tokens.
- ☐ A user cannot view/modify another user's orders, addresses, or wallet by
  changing the ID in the URL.
- ☐ Prices/discounts cannot be tampered client-side (server re-prices).
- ☐ Rate limits enforced on login, register, newsletter, coupon, checkout, search.
- ☐ `/account/*` and `/admin/*` are not accessible unauthenticated.

---

## Regression note — Google avatar & name (2026-08-16)

Recently fixed: the Google profile photo is stored on sign-in but was not being
displayed, and the account dropdown showed a static label instead of the user's
name. When testing sections **2, 9, 10**, explicitly confirm:

- ☐ Header dropdown shows the real name + photo.
- ☐ Account header ("Hi, {name}") and profile card show the photo, not just the
  initial.
- ☐ Users without a photo still fall back to the initial/user icon cleanly.
