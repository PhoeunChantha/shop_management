<?php

use App\Services\Admin\OverviewReportService;
use App\Services\Admin\ProductReportService;
use App\Services\Admin\Reports\ReportFilters;

it('computes period-over-period comparison for the overview', function () {
    $report = app(OverviewReportService::class)->report(ReportFilters::fromArray([]), true);

    expect($report['comparison'])->toHaveKeys(['total_sales', 'net_sales', 'orders', 'average_order_value', 'gross_profit']);

    foreach ($report['comparison'] as $metric) {
        expect($metric)->toHaveKeys(['previous', 'change', 'direction'])
            ->and($metric['direction'])->toBeIn(['up', 'down', 'flat']);
        // Never a fabricated percentage against a zero base.
        if ($metric['previous'] == 0) {
            expect($metric['change'])->toBeNull();
        }
    }
});

it('derives gross profit as net sales minus the cost snapshot', function () {
    $report = app(OverviewReportService::class)->report(ReportFilters::fromArray([]), true);

    expect($report['finance'])->toHaveKeys(['cogs', 'gross_profit', 'margin', 'uncosted_units'])
        ->and($report['finance']['gross_profit'])->toBe(round($report['summary']['net_sales'] - $report['finance']['cogs'], 2));

    $products = app(ProductReportService::class)->report([])['summary'];
    expect($products)->toHaveKeys(['cogs', 'profit', 'margin']);
});
