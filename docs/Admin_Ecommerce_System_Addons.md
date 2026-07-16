# Admin E-commerce System Add-ons

Shop Management full admin feature plan - 2026-07-16

This document lists high-value add-ons for making the admin panel feel like a complete e-commerce operations system. The order below prioritizes features that build naturally on the admin work already completed.

## Recommended Build Order

| Priority | Feature | Purpose | Suggested Scope |
|---|---|---|---|
| 1 | Image optimization | Improve upload quality, storage, and storefront performance. | Done: compress uploads, generate thumbnails, store original/optimized size, and show admin optimization status. |
| 2 | Bulk product actions | Speed up catalog maintenance. | Done: selected export, guarded delete, status, category, brand, and flag updates. |
| 3 | Product import review | Make CSV/Excel imports safer. | Upload file, preview rows, validate errors, then confirm import. |
| 4 | Customer management | Give admins a full customer view. | Customer list, order history, lifetime spend, notes, tags, and blocked/VIP status. |
| 5 | Stock movement history | Make inventory changes auditable. | Track stock in/out, reason, related order, admin actor, and timestamp. |
| 6 | Return and refund management | Handle post-purchase support cleanly. | Return requests, refund status, reason, admin notes, and order timeline events. |
| 7 | Notification center | Centralize urgent admin alerts. | Low stock, new orders, failed payments, pending reviews, and media cleanup warnings. |
| 8 | Advanced dashboard analytics | Improve daily decision making. | Revenue chart, top products, low stock, recent orders, conversion summary, and payment status widgets. |
| 9 | SEO manager | Improve product and category discoverability. | Missing meta report, slug checks, duplicate title warnings, and SEO completion score. |
| 10 | Supplier restock workflow | Support purchasing and replenishment. | Supplier records, purchase orders, incoming stock, and receiving history. |
| 11 | Abandoned cart | Recover missed sales. | Track incomplete carts, customer/contact details, products, and reminder status. |
| 12 | Admin permission audit | Make team access safer. | Role permission comparison, permission change log, and admin access review page. |

## Phase 1: Media And Catalog Operations

### Image Optimization

Best next feature because the media library, picker modal, and upload workflow already exist.

- Done: compress uploaded JPG, PNG, and WebP images when GD can make a smaller file.
- Done: generate smaller WebP thumbnails for admin media grids.
- Done: store original size, optimized size, dimensions, and optimization status.
- Done: keep SVG and GIF uploads unchanged with a clear skipped status.
- Done: show optimization status in the media library and preview modal.
- Done: add a batch action to process pending existing media files.

### Bulk Product Actions

- Done: add checkbox selection to product table rows.
- Done: add bulk action bar for status, category, brand, export, flags, and delete.
- Done: require confirmation for destructive delete actions.
- Done: reuse existing table shell and status chips.
- Remaining later: log each bulk action to a wider product audit log when product activity events are added.

### Product Import Review

- Accept CSV first, Excel later if needed.
- Map columns to product fields.
- Preview valid and invalid rows before saving.
- Save only after admin confirmation.
- Export rejected rows with error messages.

## Phase 2: Customers, Orders, And Support

### Customer Management

- Customer index with search and filters.
- Customer show page with order history, spend summary, addresses, and notes.
- Tags for VIP, wholesale, blocked, or high risk.
- Link customers to orders where email matches existing records.

### Return And Refund Management

- Admin return requests with status workflow.
- Return reasons and internal notes.
- Refund state separate from order status.
- Add events to the order timeline and activity log.

### Abandoned Cart

- Store cart owner, email, items, total, and last activity.
- Show abandoned carts by age and value.
- Add reminder status for future email automation.

## Phase 3: Inventory And Purchasing

### Stock Movement History

- Track every stock adjustment.
- Store old quantity, new quantity, delta, actor, and reason.
- Link order deductions back to order details.
- Add inventory movement page and product-level movement tab.

### Supplier Restock Workflow

- Supplier CRUD.
- Purchase order draft, ordered, received, and cancelled statuses.
- Incoming stock quantity.
- Receiving action that increases inventory and records stock movements.

## Phase 4: Intelligence And Governance

### Advanced Dashboard Analytics

- Revenue trend chart.
- Orders by status.
- Top products by sales.
- Low-stock and out-of-stock widgets.
- Recent admin activity widget.

### Notification Center

- Persist admin notifications in the database.
- Mark read/unread.
- Filter by alert type.
- Link alerts to related products, orders, media, or reviews.

### SEO Manager

- Product and category SEO completion report.
- Missing meta title and description filters.
- Duplicate slug/title warnings.
- Bulk SEO export for review.

### Admin Permission Audit

- Role permission matrix.
- Admin access summary.
- Permission change history.
- Last login and inactive admin review.

## Immediate Recommendation

Build **Image Optimization** next. It is the strongest continuation because the media library is already in place, and optimized images improve product management, storage, and customer-facing performance without requiring a storefront redesign.
