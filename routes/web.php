<?php

use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\AttributeController;
use App\Http\Controllers\Backend\BrandController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\CouponController;
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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

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

    Route::prefix('users')->name('users.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('products')->name('products.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('brands')->name('brands.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::get('/create', [BrandController::class, 'create'])->name('create');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [BrandController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{id}', [BrandController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('attributes')->name('attributes.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [AttributeController::class, 'index'])->name('index');
        Route::get('/create', [AttributeController::class, 'create'])->name('create');
        Route::post('/', [AttributeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AttributeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AttributeController::class, 'update'])->name('update');
        Route::delete('/{id}', [AttributeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories')->name('categories.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('sizes')->name('sizes.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [SizeController::class, 'index'])->name('index');
        Route::get('/create', [SizeController::class, 'create'])->name('create');
        Route::post('/', [SizeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [SizeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SizeController::class, 'update'])->name('update');
        Route::delete('/{id}', [SizeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('colors')->name('colors.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [ColorController::class, 'index'])->name('index');
        Route::get('/create', [ColorController::class, 'create'])->name('create');
        Route::post('/', [ColorController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ColorController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ColorController::class, 'update'])->name('update');
        Route::delete('/{id}', [ColorController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('coupons')->name('coupons.')->middleware('role:admin|manager')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('/create', [CouponController::class, 'create'])->name('create');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{id}', [CouponController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->middleware('role:admin|manager')->group(function () {
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
