<?php
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use App\Models\User;

$paid = ['paid', 'partially_refunded', 'refunded'];
echo 'paid orders 30d: '.Order::whereIn('payment_status', $paid)->where('placed_at', '>=', now()->subDays(30))->count().PHP_EOL;
echo 'gross sales: '.Order::whereIn('payment_status', $paid)->sum('grand_total').PHP_EOL;
echo 'order lines: '.OrderDetail::count().PHP_EOL;
echo 'low/out products: '.Product::where('product_type', 'single')->whereColumn('stock', '<=', 'low_stock_alert')->count().PHP_EOL;
echo 'customers: '.User::role('customer')->count().PHP_EOL;
echo 'returns: '.ReturnRequest::count().PHP_EOL;
echo 'purchase orders: '.PurchaseOrder::count().PHP_EOL;
