# Frontend Dynamic Integration Roadmap

Goal: convert every page under `resources/views/frontend` to dynamic Laravel data while preserving the current storefront UI, animations, responsive layout, and route structure.

## Rules

- Do not redesign frontend UI unless a page is broken or visually inconsistent.
- Keep controllers thin; move reusable storefront data mapping into services.
- Prefer real database models and storefront services over static demo support data.
- Do not add new storefront dependencies on `App\Support\Catalog` or `App\Support\ProductPorter`.
- Keep safe fallbacks only for empty database states.
- Validate each step with `php artisan view:cache`, `php artisan test`, and `php artisan view:clear`.

## Static Support Cleanup

- `app/Support/Catalog.php` was demo storefront data only and has been removed after replacing frontend calls with database-backed services.
- `app/Support/ProductPorter.php` belongs to admin product import/export column definitions. Do not use it for frontend dynamic data. Keep it only while import/export features depend on it.
- Before removing either file, run `rg "Catalog::|ProductPorter|App\\\\Support\\\\Catalog|App\\\\Support\\\\ProductPorter" app resources routes database tests docs -n`.

## Current Status

| Area | Files | Status | Notes |
| --- | --- | --- | --- |
| Home | `home.blade.php`, `HomeController.php` | Partial | Products, collections, reviews partly dynamic; hero/newsletter/trust blocks still use controller/static fallback data. |
| Shop Listing | `shop/index.blade.php`, `ShopController.php` | Mostly dynamic | Product grid, filters, sorting, category links, colors, and sizes are DB-backed. Needs optional server-side filter refinement. |
| Product Detail | `shop/show.blade.php` | Mostly dynamic | Slug URL, images, price, colors, sizes, related products, **and approved reviews (with product rating_avg/count)** are DB-backed. Needs shipping copy + fabric/care/specs from product specifications/settings. |
| Header/Nav/Search | `components/frontend/header.blade.php`, `FrontendNavigationService.php` | Mostly dynamic | Categories, popular products, and announcements are DB-backed with local text fallbacks. Search chips remain preset suggestions. |
| Cart | `cart/index.blade.php`, `CartController.php`, `main.js` | Partial | Cart is localStorage-based. Cross-sell and colors are DB-backed; checkout handoff is not persisted server-side. |
| Checkout | `checkout/index.blade.php`, `confirmation.blade.php`, `CheckoutController.php`, `FrontendCheckoutService.php` | Mostly dynamic | Shipping methods, payment methods, and tax are admin-backed; totals recompute live (free-over + tax). **Placing an order creates a real `Order` + `OrderDetails` (re-priced server-side), decrements stock, and the confirmation shows the real order.** Remaining: payment-gateway processing, saved addresses, and per-step field validation. |
| Account | `account/*` | Partial | User, orders, order details, notifications, colors, and products are service/DB-backed. Wishlist remains localStorage and addresses are derived from orders until saved addresses exist. |
| Auth | `auth/*` | Dynamic | **Login, register, logout, forgot + reset password are wired to real Laravel auth** (`Frontend\AuthController`): CSRF, validation errors, old input, `remember`, session regeneration. Register creates a `user`-role customer and signs in. Account routes are `auth`-guarded; post-login redirect is role-aware (admin → admin dashboard, customer → account). OTP page remains a designed stub (no OTP infra yet). |
| Content Pages | `pages/about.blade.php`, `contact.blade.php`, `faq.blade.php`, `privacy.blade.php`, `terms.blade.php` | Mostly dynamic | FAQ, **Privacy + Terms (from admin CMS Pages), and Contact (from Settings)** are DB-backed. About remains a designed marketing page (kept per "don't redesign"); wire to a CMS page if desired. |
| Layout/Shared | `layouts/frontend.blade.php`, frontend components | Partial | Global colors and product card colors are DB-backed; store name/social/footer settings still need cleanup. |

## Step 1: Shared Storefront Data

- Create `FrontendProductMapper` or extend an existing service for consistent product arrays.
- Create `FrontendStorefrontService` for store settings, social links, policy text, trust badges, shipping snippets, and legal labels.
- Keep global frontend color data loaded from `FrontendProductService`.
- Keep `Catalog` only as a temporary fallback layer.

## Step 2: Home Page

- Move remaining hero slides from hardcoded arrays to active banners/deals/settings.
- Use active deal campaigns for flash deal title, timer, and products.
- Use featured collections from `collections` table.
- Use real reviews/testimonials when available.
- Use newsletter copy from settings.

## Step 3: Shop Listing

- Keep the current card UI.
- Ensure category, subcategory, brand, size, color, sale, new, best seller, and price filters are DB-backed.
- Add server-readable query params where needed so refreshed URLs show correct data.
- Remove misleading empty states such as category labels that do not match current filters.

## Step 4: Product Detail

- Use DB product images, gallery, price, discount, colors, sizes, related products, stock, and slug.
- Replace static fabric/care/shipping tabs with product specifications and storefront settings.
- Use real approved reviews for the product.
- Keep add-to-cart localStorage behavior until checkout/order persistence is implemented.

## Step 5: Cart

- Keep cart cross-sell loaded from active related/best-seller products.
- Store product image URL in localStorage cart lines so drawer/cart/checkout show real images.
- Add stock/size validation before checkout.
- Keep current cart drawer UI.

## Step 6: Checkout

- [x] Load active shipping methods from admin shipping settings (`FrontendCheckoutService::shippingMethods`).
- [x] Load active payment methods from settings, including manual/QR (`paymentMethods`).
- [x] Live totals: shipping (per method + free-over) + tax (`taxRate`, single applicable exclusive rule) via `window.UT_CHECKOUT`.
- [x] Create real orders + order details from cart data — **`FrontendCheckoutService::placeOrder`** re-prices every line **server-side** (never trusts client prices), applies shipping+tax, decrements the matched variant/product stock (logs a `sale` `StockMovement`), all in a DB transaction.
- [x] Confirmation shows the real order (number, lines, totals) and clears the localStorage bag.
- Approach: cart stays in **localStorage**; on the final step `main.js` serializes `store.cart` into the hidden `items` field and submits `POST checkout` (`frontend.checkout.store`). Errors surface via the shared `<x-toastr />` (mapped onto the storefront `utToast`).
- **Remaining:** real payment-gateway processing (currently records `payment_status = unpaid`), saved addresses, per-step field validation, and robuster variant matching (currently matches cart size/colour labels against variant attribute values; falls back to the product base price when no variant matches).

## Step 7: Account Pages

- Keep account user data loaded from authenticated user/profile data.
- Keep account order data loaded from real orders and order items.
- Replace addresses with saved customer addresses.
- Replace notifications with real notification records.
- Replace wishlist with persisted wishlist items.
- Replace review page with real review submission.

## Step 8: Auth Pages

- Wire login, register, forgot password, reset password, and OTP forms to Laravel routes.
- Add validation errors and old input.
- Keep bare layout and current visual styling.

## Step 9: Content Pages

- Use admin-managed pages for about, privacy, and terms.
- Use FAQ records for FAQ page.
- Use settings for contact email, phone, social links, and store location copy.
- Keep placeholders only when admin content is empty.

## Suggested Add-ons After Dynamic Integration

These should be added only after the core storefront no longer depends on `Catalog`.

| Add-on | Priority | Purpose | Data Source |
| --- | --- | --- | --- |
| Persistent wishlist | High | Keep wishlist items across devices and account sessions. | `wishlist_items`, authenticated user, product slugs |
| Recently viewed products | High | Improve product discovery on product detail, cart, and account pages. | Session for guests, database for logged-in users |
| Product recommendations | High | Show related, best-seller, same-category, and same-brand products. | Products, categories, brands, order history |
| Size guide manager | High | Replace static size guide content with admin-managed sizing per category. | Settings or category metadata |
| Stock alerts | Medium | Let customers request notifications when a product/variant is back in stock. | Product variants, customer email, notifications |
| Product review workflow | Medium | Display real reviews and allow verified buyers to submit reviews. | Orders, order items, reviews |
| Shipping estimator | Medium | Preview delivery fee and estimated date before checkout. | Shipping zones, customer address, cart weight/subtotal |
| Manual payment instructions | Medium | Show QR image, bank details, and upload-proof flow during checkout. | Payment method settings, order payments |
| Search suggestions | Medium | Make header search return live products, categories, and popular searches. | Product index, categories, saved search terms |
| Frontend SEO metadata | Medium | Generate correct title, description, canonical URL, and Open Graph images. | Products, categories, pages, settings |
| Compare products | Low | Let shoppers compare product specs side by side. | Products, attributes, variants |
| Loyalty/rewards preview | Low | Show points earned on product and checkout pages. | Customer account, order totals, rewards settings |

## Completion Checklist

- [x] Shared storefront services created.
- [x] No frontend references to `Catalog::` or `ProductPorter`.
- [ ] Header/nav/search fully dynamic.
- [ ] Home page fully dynamic.
- [ ] Shop listing fully dynamic.
- [x] Product detail dynamic (incl. approved reviews + product rating). Specs/care tabs still static.
- [ ] Cart dynamic cross-sell and real image lines.
- [x] Checkout creates real orders (re-priced server-side, stock decremented).
- [x] Confirmation displays real order.
- [ ] Account dashboard/orders/addresses/notifications/wishlist dynamic.
- [x] Auth forms wired to real routes (login, register, logout, forgot/reset password; account routes guarded; role-aware redirect).
- [x] Content pages loaded from admin data/settings (Privacy, Terms, Contact, FAQ; About kept as designed page).
- [ ] Optional add-ons are prioritized after core dynamic integration.
- [ ] All frontend pages compile with `php artisan view:cache`.
- [ ] Full test suite passes.

## Recommended Execution Order

1. Shared storefront services and mappers.
2. Header/search/navigation.
3. Home page.
4. Product detail final cleanup.
5. Shop listing final cleanup.
6. Cart.
7. Checkout and confirmation.
8. Account pages.
9. Auth pages.
10. Content/legal/contact pages.
