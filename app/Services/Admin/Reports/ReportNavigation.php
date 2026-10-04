<?php

declare(strict_types=1);

namespace App\Services\Admin\Reports;

/**
 * The reporting information architecture: one sidebar entry per business
 * domain, with the domain's individual reports exposed as tabs on its page
 * (never as extra sidebar items). Single source for both the sidebar and the
 * in-page tab strip.
 */
final class ReportNavigation
{
    /**
     * Sidebar entries, in display order.
     *
     * @return array<int, array{label: string, icon: string, route: string, active: array<int, string>, permission: string}>
     */
    public static function domains(): array
    {
        return [
            ['label' => __('Overview'), 'icon' => 'fa-gauge-high', 'route' => 'admin.reports.index', 'active' => ['admin.reports.index', 'admin.reports.export'], 'permission' => 'view reports'],
            ['label' => __('Sales'), 'icon' => 'fa-chart-line', 'route' => 'admin.reports.sales', 'active' => ['admin.reports.sales', 'admin.reports.sales.*'], 'permission' => 'view sales reports'],
            ['label' => __('Products'), 'icon' => 'fa-box-open', 'route' => 'admin.reports.products', 'active' => ['admin.reports.products', 'admin.reports.products.*'], 'permission' => 'view product reports'],
            ['label' => __('Inventory'), 'icon' => 'fa-warehouse', 'route' => 'admin.reports.stock', 'active' => ['admin.reports.stock', 'admin.reports.stock.*'], 'permission' => 'view stock reports'],
            ['label' => __('Customers'), 'icon' => 'fa-user-group', 'route' => 'admin.reports.customers', 'active' => ['admin.reports.customers', 'admin.reports.customers.*', 'admin.reports.register', 'admin.reports.register.*'], 'permission' => 'view customer reports'],
            ['label' => __('Payments & Finance'), 'icon' => 'fa-credit-card', 'route' => 'admin.reports.payments', 'active' => ['admin.reports.payments', 'admin.reports.payments.*'], 'permission' => 'view payment reports'],
            ['label' => __('Purchasing'), 'icon' => 'fa-clipboard-list', 'route' => 'admin.reports.purchasing', 'active' => ['admin.reports.purchasing', 'admin.reports.purchasing.*'], 'permission' => 'view purchasing reports'],
            ['label' => __('Returns & Refunds'), 'icon' => 'fa-rotate-left', 'route' => 'admin.reports.returns', 'active' => ['admin.reports.returns', 'admin.reports.returns.*'], 'permission' => 'view return reports'],
        ];
    }

    /**
     * In-page report tabs for a domain.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tabs(string $domain): array
    {
        return match ($domain) {
            'customers' => [
                ['label' => __('Customer overview'), 'icon' => 'fa-user-group', 'route' => 'admin.reports.customers', 'permission' => 'view customer reports'],
                ['label' => __('Registrations'), 'icon' => 'fa-user-plus', 'route' => 'admin.reports.register', 'permission' => 'view register reports'],
            ],
            default => [],
        };
    }
}
