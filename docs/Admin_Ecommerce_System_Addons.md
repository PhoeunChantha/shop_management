# Admin E-commerce System Add-ons

Shop Management full admin feature plan - 2026-07-16

This document lists high-value add-ons for making the admin panel feel like a complete e-commerce operations system. The order below prioritizes features that build naturally on the admin work already completed.

## Recommended Build Order

| Priority | Feature | Purpose | Suggested Scope |
|---|---|---|---|
| 1 | Image optimization | Improve upload quality, storage, and storefront performance. | Done: compress uploads, generate thumbnails, store original/optimized size, and show admin optimization status. |
| 2 | Bulk product actions | Speed up catalog maintenance. | Done: selected export, guarded delete, status, category, brand, and flag updates. |
| 3 | Product import review | Make CSV/Excel imports safer. | Done: upload file, dry-run validation, preview rows/errors, then confirm or cancel import. |
| 4 | Customer management | Give admins a full customer view. | Done: customer list, order history, lifetime spend, top products, bulk enable/disable/export/delete, CRM notes/tags, and `CustomerService`. |
| 5 | Offers & Deals | Manage flash deals, daily offers, featured deals, and clearance campaigns. | Done: unified deal campaigns, product assignment, image/media support, lifecycle filters, bulk actions, and deal permissions. |
| 6 | Stock movement history | Make inventory changes auditable. | Track stock in/out, reason, related order, admin actor, and timestamp. |
| 7 | Return and refund management | Handle post-purchase support cleanly. | Done: return requests, selected order items, refund workflow, admin notes, and order timeline events. |
| 8 | Notification center | Centralize urgent admin alerts. | Done: persisted admin alerts for orders, returns, stock, reviews, media optimization, and expiring deals with read/unread workflow. |
| 9 | Advanced dashboard analytics | Improve daily decision making. | Done: revenue trends, KPI sparklines, status/payment mix, control queue, top products, fulfillment pulse, recent orders, and low-stock widgets. |
| 10 | SEO manager | Improve product and category discoverability. | Done: product/category/page SEO audit, completion scores, issue filters, duplicate warnings, quick metadata edits, and CSV export. |
| 11 | Supplier restock workflow | Support purchasing and replenishment. | Done: supplier records, purchase orders, incoming stock lines, receive workflow, stock movement integration, and restock permissions. |
| 12 | Abandoned cart | Recover missed sales. | Done: abandoned cart records, item details, value/age filters, recovery statuses, admin notes, export, dashboard queue, and notifications. |
| 13 | Admin permission audit | Make team access safer. | Done: role permission matrix, role comparison, risky grant summary, direct user permission review, stale admin review, and CSV export. |
| 14 | Finance & reporting exports | Centralize money reporting for admin decisions. | Done: finance report dashboard, date/status/payment filters, net sales, refunds, tax, shipping, purchase cost, top products, top customers, payment mix, and CSV exports. |

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

- Done: accept CSV, TXT, XLS, and XLSX using the existing Laravel Excel importer.
- Done: map columns through the existing product template headings.
- Done: preview valid rows and invalid rows before saving.
- Done: save only after admin confirmation.
- Remaining later: export rejected rows with error messages.

### Offers & Deals

- Done: add `deal_campaigns` for flash deals, deal of the day, featured deals, and clearance sales.
- Done: attach campaign images through the admin media library in a dedicated `deals` folder.
- Done: assign selected products to each campaign.
- Done: support discount type/value, campaign timing, priority, status, CTA fields, and SEO metadata.
- Done: add admin table filters, bulk enable/disable/delete, detail page, and deal permissions.
- Remaining later: wire active campaigns into storefront Flash Sale and offer landing surfaces when frontend changes are approved.

## Phase 2: Customers, Orders, And Support

### Customer Management

- Done: customer index with search, filters, sort, and pagination.
- Done: customer show page with order history, spend summary, contact/location, and top products.
- Done: link customers to orders by checkout email.
- Done: persist admin customer state in `customer_profiles` so enable, disable, and delete actions survive reloads without changing order history.
- Done: add customer bulk actions for enable, disable, selected export, and guarded delete confirmation.
- Done: move customer query, stats, profile sync, export rows, and bulk mutations into `App\Services\CustomerService`.
- Done: add persistent internal notes and reusable tags for VIP, wholesale, risk, and blocked customer CRM labeling.
- Remaining later: customer activity timeline and support ownership assignment.

### Admin Service Architecture

- Done: product, order, dashboard, inventory, review, setting, media, attribute, bulk actions, and customer workflows use service classes where business logic is non-trivial.
- Done: extracted reusable query, import/export, media, bulk mutation, role/permission, and file/image workflows from heavier backend controllers into service classes.
- Next: keep future admin modules service-backed from the first implementation pass.
- Keep thin controllers: validate input, authorize the action, delegate to a service, and return a response.

### Return And Refund Management

- Done: admin return request list, create flow from orders, and detail workflow page.
- Done: return statuses: requested, approved, rejected, received, refunded.
- Done: refund statuses: not refunded, pending, partial, refunded.
- Done: selected order items, quantities, condition notes, requested amount, and refund amount.
- Done: add return/refund events to the order activity timeline and update order payment state when refunds complete.
- Remaining later: customer self-service return request page and payment gateway refund integration.

### Abandoned Cart

- Done: store cart owner, email, phone, items, total, and last activity.
- Done: show abandoned carts by age, value, search, and recovery status.
- Done: recovery statuses: new, contacted, recovered, ignored.
- Done: admin notes, detail page, and CSV export.
- Done: dashboard control queue count and high-value abandoned cart notifications.
- Remaining later: connect live storefront cart tracking and automated email/SMS reminders when frontend/customer messaging work is approved.

## Phase 3: Inventory And Purchasing

### Stock Movement History

- Track every stock adjustment.
- Store old quantity, new quantity, delta, actor, and reason.
- Link order deductions back to order details.
- Add inventory movement page and product-level movement tab.

### Supplier Restock Workflow

- Done: supplier CRUD with active/inactive state.
- Done: purchase order draft, ordered, received, and cancelled statuses.
- Done: incoming product/variant stock lines with quantity and unit cost.
- Done: receiving action increases inventory through `StockService`.
- Done: received stock writes stock movement history using the existing restock audit path.
- Remaining later: partial receiving quantities and supplier cost history.

## Phase 4: Intelligence And Governance

### Advanced Dashboard Analytics

- Done: revenue trend chart with selectable 7-day, 30-day, and 12-month windows.
- Done: KPI cards with trend comparison and sparklines.
- Done: order status and payment mix charts.
- Done: control queue for pending orders, unpaid orders, returns, reviews, stock alerts, and unread notifications.
- Done: top products by sold quantity and revenue for the selected range.
- Done: low-stock widget and fulfillment pulse.
- Remaining later: conversion metrics when storefront event tracking is added.

### Notification Center

- Done: persist admin notifications in the database with duplicate-safe fingerprints.
- Done: generate alerts for pending orders, unpaid orders, requested returns, low/out-of-stock products, pending reviews, media optimization issues, and expiring deals.
- Done: show live alerts in the admin header dropdown.
- Done: mark read/unread and mark all read.
- Done: filter by alert type, priority, read state, and search text.
- Done: link alerts to related products, orders, returns, media, reviews, or deals.

### SEO Manager

- Done: product, category, and page SEO completion report.
- Done: missing title/description and title/description length checks.
- Done: duplicate slug/title warnings across indexed admin records.
- Done: SEO score per record with filterable issue states.
- Done: quick edit SEO title/description from one admin table.
- Done: CSV export for review.

### Admin Permission Audit

- Done: role permission matrix for every Spatie role and permission.
- Done: role-to-role comparison with difference-only rows.
- Done: risky permission summary for delete, settings, users, roles, permissions, supplier, and purchase order grants.
- Done: direct user permission review so role bypasses are visible.
- Done: stale admin record review using old admin, manager, and staff profile update dates.
- Done: CSV export of the permission matrix.
- Remaining later: permission change history and last-login audit when login tracking is added.

## Immediate Recommendation

Build admin login history next, or wire Offers & Deals into the storefront when frontend changes are approved. Keep storefront/frontend files untouched unless a separate frontend task is requested.
