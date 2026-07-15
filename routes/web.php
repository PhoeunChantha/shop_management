<?php

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\InventoryController;
use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\ReviewController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\ShippingMethodController;
use App\Http\Controllers\Backend\TaxRuleController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\AnnouncementController;
use App\Http\Controllers\Backend\AttributeController;
use App\Http\Controllers\Backend\BannerController;
use App\Http\Controllers\Backend\BrandController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\CollectionController;
use App\Http\Controllers\Backend\CouponController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\MediaAssetController;
use App\Http\Controllers\Backend\OrderController;
use App\Http\Controllers\Backend\PageController as AdminPageController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\SizeController;
use App\Http\Controllers\Backend\ColorController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ShopController;

use Illuminate\Support\Facades\Route;

Route::name('frontend.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // ---- Shop ----
    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::get('/shop/{id}', [ShopController::class, 'show'])->whereNumber('id')->name('shop.show');

    // ---- Cart & Checkout ----
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::get('/checkout/confirmation', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

    // ---- Authentication ----
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
    Route::get('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
    Route::get('/verify-otp', [AuthController::class, 'otp'])->name('otp.verify');

    // ---- Customer Account ----
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::get('/password', [AccountController::class, 'password'])->name('password');
        Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
        Route::get('/notifications', [AccountController::class, 'notifications'])->name('notifications');
        Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{id}', [AccountController::class, 'orderDetail'])->name('orders.show');
        Route::get('/orders/{id}/tracking', [AccountController::class, 'orderTracking'])->name('orders.tracking');
        Route::get('/orders/{id}/review/{pid}', [AccountController::class, 'review'])->name('orders.review');
    });

    // ---- Information pages ----
    Route::get('/about', [PageController::class, 'about'])->name('pages.about');
    Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
    Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
    Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
});

// ---- Admin ----

Route::get('admin/login', function () {
    return view('auth.login');
})->middleware('guest')->name('admin.login');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])
            ->middleware('permission:view permission')
            ->name('index');
        Route::get('/create', [PermissionController::class, 'create'])
            ->middleware('permission:create permission')
            ->name('create');
        Route::post('/', [PermissionController::class, 'store'])
            ->middleware('permission:create permission')
            ->name('store');
        Route::get('/{id}/edit', [PermissionController::class, 'edit'])
            ->middleware('permission:edit permission')
            ->name('edit');
        Route::put('/{id}', [PermissionController::class, 'update'])
            ->middleware('permission:edit permission')
            ->name('update');
        Route::delete('/{id}', [PermissionController::class, 'destroy'])
            ->middleware('permission:delete permission')
            ->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->middleware('role:admin|manager|staff')
            ->name('index');
        Route::middleware('role:admin|manager')->group(function () {
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::delete('/bulk', [ProductController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [ProductController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/export', [ProductController::class, 'export'])->name('export');
        Route::get('/template', [ProductController::class, 'template'])->name('template');
        Route::post('/import', [ProductController::class, 'import'])->name('import');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::delete('/bulk', [BrandController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [BrandController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [BrandController::class, 'create'])->name('create');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [BrandController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{id}', [BrandController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('attributes')->name('attributes.')->group(function () {
        Route::get('/', [AttributeController::class, 'index'])->name('index');
        Route::delete('/bulk', [AttributeController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [AttributeController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [AttributeController::class, 'create'])->name('create');
        Route::post('/', [AttributeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AttributeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AttributeController::class, 'update'])->name('update');
        Route::delete('/{id}', [AttributeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::delete('/bulk', [CategoryController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [CategoryController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('sizes')->name('sizes.')->group(function () {
        Route::get('/', [SizeController::class, 'index'])->name('index');
        Route::delete('/bulk', [SizeController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [SizeController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [SizeController::class, 'create'])->name('create');
        Route::post('/', [SizeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [SizeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SizeController::class, 'update'])->name('update');
        Route::delete('/{id}', [SizeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('colors')->name('colors.')->group(function () {
        Route::get('/', [ColorController::class, 'index'])->name('index');
        Route::delete('/bulk', [ColorController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [ColorController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [ColorController::class, 'create'])->name('create');
        Route::post('/', [ColorController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ColorController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ColorController::class, 'update'])->name('update');
        Route::delete('/{id}', [ColorController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::patch('/bulk-moderate', [ReviewController::class, 'bulkModerate'])->name('bulk-moderate');
        Route::delete('/bulk', [ReviewController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/{id}', [ReviewController::class, 'moderate'])->whereNumber('id')->name('moderate');
        Route::delete('/{id}', [ReviewController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/{id}', [InventoryController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/adjust', [InventoryController::class, 'adjust'])->whereNumber('id')->name('adjust');
    });

    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaAssetController::class, 'index'])->name('index');
        Route::get('/picker', [MediaAssetController::class, 'picker'])->name('picker');
        Route::post('/', [MediaAssetController::class, 'store'])->name('store');
        Route::delete('/{media}', [MediaAssetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/invoice', [OrderController::class, 'invoice'])->whereNumber('id')->name('invoice');
        Route::get('/{id}/packing-slip', [OrderController::class, 'packingSlip'])->whereNumber('id')->name('packing-slip');
        Route::patch('/{id}', [OrderController::class, 'update'])->whereNumber('id')->name('update');
    });

    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::delete('/bulk', [BannerController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [BannerController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [BannerController::class, 'create'])->name('create');
        Route::post('/', [BannerController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [BannerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BannerController::class, 'update'])->name('update');
        Route::delete('/{id}', [BannerController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::delete('/bulk', [AnnouncementController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [AnnouncementController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{id}', [AnnouncementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::delete('/bulk', [CollectionController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [CollectionController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [CollectionController::class, 'create'])->name('create');
        Route::post('/', [CollectionController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CollectionController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CollectionController::class, 'update'])->name('update');
        Route::delete('/{id}', [CollectionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::delete('/bulk', [CouponController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [CouponController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [CouponController::class, 'create'])->name('create');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{id}', [CouponController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [AdminPageController::class, 'index'])->name('index');
        Route::delete('/bulk', [AdminPageController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [AdminPageController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [AdminPageController::class, 'create'])->name('create');
        Route::post('/', [AdminPageController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminPageController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminPageController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminPageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('faqs')->name('faqs.')->group(function () {
        Route::get('/', [FaqController::class, 'index'])->name('index');
        Route::delete('/bulk', [FaqController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [FaqController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [FaqController::class, 'create'])->name('create');
        Route::post('/', [FaqController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [FaqController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FaqController::class, 'update'])->name('update');
        Route::delete('/{id}', [FaqController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('shipping')->name('shipping.')->group(function () {
        Route::get('/', [ShippingMethodController::class, 'index'])->name('index');
        Route::delete('/bulk', [ShippingMethodController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [ShippingMethodController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [ShippingMethodController::class, 'create'])->name('create');
        Route::post('/', [ShippingMethodController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ShippingMethodController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ShippingMethodController::class, 'update'])->name('update');
        Route::delete('/{id}', [ShippingMethodController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('taxes')->name('taxes.')->group(function () {
        Route::get('/', [TaxRuleController::class, 'index'])->name('index');
        Route::delete('/bulk', [TaxRuleController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/bulk-status', [TaxRuleController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/create', [TaxRuleController::class, 'create'])->name('create');
        Route::post('/', [TaxRuleController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [TaxRuleController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TaxRuleController::class, 'update'])->name('update');
        Route::delete('/{id}', [TaxRuleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
    });
});

// ---- Locale switch (shared) ----
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');

require __DIR__ . '/auth.php';
