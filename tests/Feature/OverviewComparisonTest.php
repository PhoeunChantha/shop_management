<?php

use App\Services\Admin\FinanceReportService;
use App\Services\Admin\ProductReportService;

it('computes period-over-period comparison for the overview', function () {
    $report = app(FinanceReportService::class)->overview([]);

    expect($report)->toHaveKeys(['comparison', 'previousRange'])
        ->and($report['comparison'])->toHaveKeys(['total_revenue', 'net_revenue', 'orders', 'average_order']);

    foreach ($report['comparison'] as $metric) {
        expect($metric)->toHaveKeys(['previous', 'change', 'direction'])
            ->and($metric['direction'])->toBeIn(['up', 'down', 'flat']);
        // Never a fabricated percentage against a zero base.
        if ($metric['previous'] == 0) {
            expect($metric['change'])->toBeNull();
        }
    }
});

it('derives gross profit and margin from the cost snapshot', function () {
    $overview = app(FinanceReportService::class)->overview([])['summary'];

    expect($overview)->toHaveKeys(['cogs', 'gross_profit', 'margin'])
        // Gross profit = net sales − COGS.
        ->and(round($overview['gross_profit'], 2))->toBe(round($overview['net_sales'] - $overview['cogs'], 2));

    $products = app(ProductReportService::class)->report([])['summary'];
    expect($products)->toHaveKeys(['cogs', 'profit', 'margin'])
        ->and(round($products['profit'], 2))->toBe(round($products['revenue'] - $products['cogs'], 2));
});
