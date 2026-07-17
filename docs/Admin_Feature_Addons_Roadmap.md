# Admin Feature Add-ons Roadmap

Shop Management admin recommendations - 2026-07-15

**Recommendation:** Build the admin improvements in layers: first stabilize shared components, then improve operational workflows, then add analytics.

**Full system plan:** See [Admin_Ecommerce_System_Addons.md](Admin_Ecommerce_System_Addons.md) for the complete e-commerce admin add-on roadmap.

## Best Next Features

- Reusable admin table shell for filters, search, bulk actions, empty states, and pagination.
- Order operations workflow with status timeline, payment state, fulfillment notes, and printable invoice.
- Inventory alert center for low stock, out-of-stock variants, and reorder suggestions.
- Admin dashboard KPIs for sales, pending orders, active products, low stock, and top products.
- Product form section collapse so long product setup stays fast and clean.
- Media library for product, banner, brand, and collection images with reuse and cleanup.
- Activity log export and admin audit filters for user, module, action, date, and IP.

## Feature Priority Matrix

| Priority | Feature | Why it matters | Suggested build |
|---|---|---|---|
| 1 | Reusable table system | Makes every admin list consistent and faster to redesign. | Create Blade component, then migrate categories and brands first. |
| 2 | Order management polish | Most important daily e-commerce workflow after product setup. | Improve index filters, show page timeline, status actions, and invoice print. |
| 3 | Inventory alerts | Prevents selling unavailable products and helps restock planning. | Add low-stock query, alert page, and dashboard widget. |
| 4 | Dashboard KPIs | Gives admin users an immediate store health overview. | Add cards, recent orders, top products, and low-stock list. |
| 5 | Media library | Reduces duplicate uploads and broken images across admin. | Create image browser modal and attach existing assets to records. |
| 6 | Bulk operations | Saves time for product/status/category maintenance. | Bulk status update, delete, export, and import review screen. |

## Suggested Implementation Order

1. Fix shared admin primitives: page header, table shell, filters, empty state, and status chips.
2. Apply the table shell to simple catalog pages first: categories, brands, sizes, colors, and tags.
3. Upgrade order management because it has the highest operational value.
4. Add dashboard widgets using the same table and card primitives.
5. Add media library and bulk operations once the admin visual system is stable.

## Implementation Progress

- Done: reusable admin page header component.
- Done: reusable admin table card component.
- Done: reusable admin empty state component.
- Done: migrated the brands index table to the reusable table card.
- Done: migrated the categories index table to the reusable table card.
- Done: migrated the sizes index table to the reusable table card.
- Done: migrated the colors index table to the reusable table card.
- Done: migrated the attributes index table to the reusable table card.
- Done: migrated the roles index table to the reusable table card.
- Done: migrated the permissions index table to the reusable table card.
- Done: migrated the users index table to the reusable table card.
- Done: migrated the orders index table to the reusable table card.
- Done: polished the order detail workflow surface.
- Done: migrated the inventory index and detail tables to the reusable table card.
- Done: migrated the reviews, coupons, shipping, and taxes index tables to the reusable table card.
- Done: migrated pages, FAQs, announcements, banners, collections, and products index tables to the reusable table card.
- Done: added the first media library slice with database storage, upload/delete actions, admin route, sidebar entry, search/filter UI, and responsive image grid.
- Done: added the media picker endpoint and integrated library selection into banner, brand, category, and collection image fields.
- Done: integrated the media picker into product thumbnail, gallery, variant images, and settings logo/favicon fields.
- Done: redesigned the shared image field so clicking the preview opens a media modal with Library and Upload new tabs.
- Done: improved the media library with preview modal, copy URL/path actions, drag/drop upload feedback, usage badges, and delete safety.
- Done: restored backend auth/profile compatibility routes and fixed the auth/profile test failures.
- Done: added an admin activity log with audit filters, searchable order events, and CSV export.
- Done: created the full e-commerce admin add-ons plan in `docs/Admin_Ecommerce_System_Addons.md`.
- Done: added image optimization metadata, thumbnail generation, upload compression, and a pending media optimizer action.
- Done: expanded product bulk actions with selected export, status, category, brand, flag, and guarded delete workflows.
- Done: added product import review with dry-run validation, preview rows, error summary, confirm, and cancel workflow.
- Done: added customer management from order history with customer list, profile page, spend summary, order history, and top products.
- Done: added customer bulk enable, disable, selected export, and guarded delete workflow using persistent customer profile state.
- Done: extracted customer business/query logic into `App\Services\CustomerService` so `CustomerController` stays thin.
- Done: added customer CRM notes and tags with persistent profiles, tag filtering, show-page editing, and reusable service-backed persistence.
- Done: added admin Offers & Deals with unified deal campaigns for flash, daily, featured, and clearance promotions.
- Done: refactored heavier backend controllers into service-backed workflows for activity log, users, roles, permissions, inventory, media assets, reviews, product import/bulk operations, order read models, and shared image fields.
- Next: add return/refund management, or connect Offers & Deals to storefront promo sections when frontend changes are approved.

## Build Notes

Keep the admin design dense, predictable, and task-focused. Avoid marketing-style sections inside operational pages. Use the product table visual language as the base system, but extract it into reusable Blade components before applying it to many pages.

Architecture direction: controllers should validate, authorize, and return responses. Query composition, persistence workflows, bulk mutations, exports, and cross-model operations should live in `app/Services`. Simple CRUD controllers can keep direct model calls only when the logic is genuinely small and not reused.
