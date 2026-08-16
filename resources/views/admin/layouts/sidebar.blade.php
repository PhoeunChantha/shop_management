@php
    $modules = [
        [
            'type' => 'link',
            'label' => __('Dashboard'),
            'caption' => __('Store overview'),
            'icon' => 'fa-gauge-high',
            'route' => 'admin.dashboard',
            'routes' => ['admin.dashboard'],
        ],
        [
            'label' => __('Reports'),
            'caption' => __('Analytics and exports'),
            'icon' => 'fa-chart-column',
            'routes' => ['admin.reports.*'],
            'items' => [
                ['label' => __('Overview'), 'icon' => 'fa-gauge-high', 'route' => 'admin.reports.index', 'active' => ['admin.reports.index', 'admin.reports.export'], 'permission' => 'view reports'],
                ['label' => __('Sales'), 'icon' => 'fa-chart-line', 'route' => 'admin.reports.sales', 'active' => ['admin.reports.sales', 'admin.reports.sales.*'], 'permission' => 'view sales reports'],
                ['label' => __('Products'), 'icon' => 'fa-box-open', 'route' => 'admin.reports.products', 'active' => ['admin.reports.products', 'admin.reports.products.*'], 'permission' => 'view product reports'],
                ['label' => __('Stock'), 'icon' => 'fa-warehouse', 'route' => 'admin.reports.stock', 'active' => ['admin.reports.stock', 'admin.reports.stock.*'], 'permission' => 'view stock reports'],
                ['label' => __('Payments'), 'icon' => 'fa-credit-card', 'route' => 'admin.reports.payments', 'active' => ['admin.reports.payments', 'admin.reports.payments.*'], 'permission' => 'view payment reports'],
                ['label' => __('Customers'), 'icon' => 'fa-user-group', 'route' => 'admin.reports.customers', 'active' => ['admin.reports.customers', 'admin.reports.customers.*'], 'permission' => 'view customer reports'],
                ['label' => __('Purchasing'), 'icon' => 'fa-clipboard-list', 'route' => 'admin.reports.purchasing', 'active' => ['admin.reports.purchasing', 'admin.reports.purchasing.*'], 'permission' => 'view purchasing reports'],
                ['label' => __('Registrations'), 'icon' => 'fa-user-plus', 'route' => 'admin.reports.register', 'active' => ['admin.reports.register', 'admin.reports.register.*'], 'permission' => 'view register reports'],
                ['label' => __('Returns'), 'icon' => 'fa-rotate-left', 'route' => 'admin.reports.returns', 'active' => ['admin.reports.returns', 'admin.reports.returns.*'], 'permission' => 'view return reports'],
            ],
        ],
        [
            'label' => __('Sales'),
            'caption' => __('Orders and customers'),
            'icon' => 'fa-receipt',
            'routes' => ['admin.orders.*', 'admin.customers.*', 'admin.returns.*', 'admin.abandoned-carts.*', 'admin.payments.*', 'admin.wallets.*'],
            'items' => [
                ['label' => __('Orders'), 'icon' => 'fa-list-check', 'route' => 'admin.orders.index', 'active' => ['admin.orders.*']],
                ['label' => __('Payments'), 'icon' => 'fa-credit-card', 'route' => 'admin.payments.index', 'active' => ['admin.payments.*']],
                ['label' => __('Wallets'), 'icon' => 'fa-wallet', 'route' => 'admin.wallets.index', 'active' => ['admin.wallets.*']],
                ['label' => __('Customers'), 'icon' => 'fa-user-group', 'route' => 'admin.customers.index', 'active' => ['admin.customers.*']],
                ['label' => __('Returns & Refunds'), 'icon' => 'fa-rotate-left', 'route' => 'admin.returns.index', 'active' => ['admin.returns.*']],
                ['label' => __('Abandoned Carts'), 'icon' => 'fa-cart-arrow-down', 'route' => 'admin.abandoned-carts.index', 'active' => ['admin.abandoned-carts.*']],
            ],
        ],
        [
            'label' => __('Catalog'),
            'caption' => __('Products and stock'),
            'icon' => 'fa-store',
            'routes' => ['admin.products.*', 'admin.inventory.*', 'admin.suppliers.*', 'admin.purchase-orders.*', 'admin.reviews.*', 'admin.brands.*', 'admin.categories.*', 'admin.attributes.*', 'admin.sizes.*', 'admin.colors.*'],
            'items' => [
                ['label' => __('Products'), 'icon' => 'fa-box-open', 'route' => 'admin.products.index', 'active' => ['admin.products.*']],
                ['label' => __('Inventory'), 'icon' => 'fa-warehouse', 'route' => 'admin.inventory.index', 'active' => ['admin.inventory.index', 'admin.inventory.show', 'admin.inventory.adjust']],
                ['label' => __('Reorder Alerts'), 'icon' => 'fa-truck-ramp-box', 'route' => 'admin.inventory.reorder', 'active' => ['admin.inventory.reorder', 'admin.inventory.reorder.*']],
                ['label' => __('Purchase Orders'), 'icon' => 'fa-clipboard-list', 'route' => 'admin.purchase-orders.index', 'active' => ['admin.purchase-orders.*']],
                ['label' => __('Suppliers'), 'icon' => 'fa-truck-field', 'route' => 'admin.suppliers.index', 'active' => ['admin.suppliers.*']],
                ['label' => __('Reviews'), 'icon' => 'fa-star', 'route' => 'admin.reviews.index', 'active' => ['admin.reviews.*']],
                ['label' => __('Brands'), 'icon' => 'fa-tags', 'route' => 'admin.brands.index', 'active' => ['admin.brands.*']],
                ['label' => __('Categories'), 'icon' => 'fa-layer-group', 'route' => 'admin.categories.index', 'active' => ['admin.categories.*']],
                ['label' => __('Attributes'), 'icon' => 'fa-sliders', 'route' => 'admin.attributes.index', 'active' => ['admin.attributes.*']],
                ['label' => __('Sizes'), 'icon' => 'fa-ruler-combined', 'route' => 'admin.sizes.index', 'active' => ['admin.sizes.*']],
                ['label' => __('Colors'), 'icon' => 'fa-palette', 'route' => 'admin.colors.index', 'active' => ['admin.colors.*']],
            ],
        ],
        [
            'label' => __('Marketing'),
            'caption' => __('Campaign surfaces'),
            'icon' => 'fa-bullhorn',
            'routes' => ['admin.coupons.*', 'admin.deals.*', 'admin.banners.*', 'admin.collections.*', 'admin.announcements.*', 'admin.media.*', 'admin.subscribers.*'],
            'items' => [
                ['label' => __('Coupons'), 'icon' => 'fa-ticket', 'route' => 'admin.coupons.index', 'active' => ['admin.coupons.*']],
                ['label' => __('Offers & Deals'), 'icon' => 'fa-tags', 'route' => 'admin.deals.index', 'active' => ['admin.deals.*']],
                ['label' => __('Banners'), 'icon' => 'fa-images', 'route' => 'admin.banners.index', 'active' => ['admin.banners.*']],
                ['label' => __('Media Library'), 'icon' => 'fa-photo-film', 'route' => 'admin.media.index', 'active' => ['admin.media.*']],
                ['label' => __('Collections'), 'icon' => 'fa-layer-group', 'route' => 'admin.collections.index', 'active' => ['admin.collections.*']],
                ['label' => __('Announcement Bar'), 'icon' => 'fa-bullhorn', 'route' => 'admin.announcements.index', 'active' => ['admin.announcements.*']],
                ['label' => __('Subscribers'), 'icon' => 'fa-envelope', 'route' => 'admin.subscribers.index', 'active' => ['admin.subscribers.*']],
            ],
        ],
        [
            'label' => __('Content'),
            'caption' => __('Pages and SEO'),
            'icon' => 'fa-file-lines',
            'routes' => ['admin.pages.*', 'admin.faqs.*', 'admin.seo.*'],
            'items' => [
                ['label' => __('Pages'), 'icon' => 'fa-file-lines', 'route' => 'admin.pages.index', 'active' => ['admin.pages.*']],
                ['label' => __('SEO Manager'), 'icon' => 'fa-magnifying-glass-chart', 'route' => 'admin.seo.index', 'active' => ['admin.seo.*']],
                ['label' => __('FAQ'), 'icon' => 'fa-circle-question', 'route' => 'admin.faqs.index', 'active' => ['admin.faqs.*']],
            ],
        ],
        [
            'label' => __('Access'),
            'caption' => __('Team permissions'),
            'icon' => 'fa-shield-halved',
            'routes' => ['admin.users.*', 'admin.roles.*', 'admin.permissions.*', 'admin.permission-audit.*'],
            'items' => [
                ['label' => __('Users'), 'icon' => 'fa-users', 'route' => 'admin.users.index', 'active' => ['admin.users.*']],
                ['label' => __('Roles'), 'icon' => 'fa-user-shield', 'route' => 'admin.roles.index', 'active' => ['admin.roles.*']],
                ['label' => __('Permissions'), 'icon' => 'fa-key', 'route' => 'admin.permissions.index', 'active' => ['admin.permissions.*']],
                ['label' => __('Permission Audit'), 'icon' => 'fa-clipboard-check', 'route' => 'admin.permission-audit.index', 'active' => ['admin.permission-audit.*']],
            ],
        ],
        [
            'label' => __('Operations'),
            'caption' => __('Store configuration'),
            'icon' => 'fa-gear',
            'routes' => ['admin.setup-health.*', 'admin.shipping.*', 'admin.taxes.*', 'admin.saved-views.*', 'admin.notifications.*', 'admin.settings.*', 'admin.activity.*'],
            'items' => [
                ['label' => __('Setup Health'), 'icon' => 'fa-list-check', 'route' => 'admin.setup-health.index', 'active' => ['admin.setup-health.*']],
                ['label' => __('Shipping Methods'), 'icon' => 'fa-truck', 'route' => 'admin.shipping.index', 'active' => ['admin.shipping.*']],
                ['label' => __('Tax Rules'), 'icon' => 'fa-percent', 'route' => 'admin.taxes.index', 'active' => ['admin.taxes.*']],
                ['label' => __('Saved Views'), 'icon' => 'fa-bookmark', 'route' => 'admin.saved-views.index', 'active' => ['admin.saved-views.*']],
                ['label' => __('Notifications'), 'icon' => 'fa-bell', 'route' => 'admin.notifications.index', 'active' => ['admin.notifications.*']],
                ['label' => __('Settings'), 'icon' => 'fa-gear', 'route' => 'admin.settings.index', 'active' => ['admin.settings.*']],
                ['label' => __('Activity Log'), 'icon' => 'fa-clock-rotate-left', 'route' => 'admin.activity.index', 'active' => ['admin.activity.*']],
            ],
        ],
    ];
@endphp

<nav class="admin-sidebar d-flex flex-column h-100 shrink-0">

    <div class="admin-brand flex-shrink-0">
        <div class="d-flex align-items-center">
            @if (!empty($adminLogo))
                <img src="{{ $adminLogo }}" alt="{{ $adminSiteName ?? 'Logo' }}" class="admin-brand-logo">
            @else
                <div class="brand-mark d-flex align-items-center justify-content-center text-white fw-bold fs-5">
                    <i class="fa-solid fa-shirt"></i>
                </div>
            @endif
            <div class="ms-3 admin-brand-copy">
                <h1 class="fw-bold text-white fs-6 mb-0 lh-sm">{{ $adminSiteName ?? 'T-Shirt Shop' }}</h1>
                <span class="brand-kicker font-medium">{{ __('Commerce Console') }}</span>
            </div>
        </div>
    </div>

    <div class="admin-nav-scroll flex-grow-1 overflow-auto">
        <p class="admin-nav-heading admin-nav-heading--module">{{ __('Modules') }}</p>

        @foreach ($modules as $module)
            @php($moduleActive = request()->routeIs(...$module['routes']))
            @if (($module['type'] ?? 'group') === 'link')
                <a href="{{ route($module['route']) }}" class="admin-module admin-module--link {{ $moduleActive ? 'is-open' : '' }}">
                    <span class="admin-module__toggle {{ $moduleActive ? 'has-active' : '' }}">
                        <span class="admin-module__icon"><i class="fa-solid {{ $module['icon'] }}"></i></span>
                        <span class="admin-module__copy">
                            <strong>{{ $module['label'] }}</strong>
                            <small>{{ $module['caption'] }}</small>
                        </span>
                    </span>
                </a>
            @else
                <section class="admin-module" x-data="{ open: {{ $moduleActive ? 'true' : 'false' }} }" :class="{ 'is-open': open }">
                    <button type="button" class="admin-module__toggle {{ $moduleActive ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="admin-module__icon"><i class="fa-solid {{ $module['icon'] }}"></i></span>
                        <span class="admin-module__copy">
                            <strong>{{ $module['label'] }}</strong>
                            <small>{{ $module['caption'] }}</small>
                        </span>
                        <i class="fa-solid fa-chevron-down admin-module__caret"></i>
                    </button>

                    <div class="admin-module__submenu">
                        <div class="admin-module__submenu-inner">
                            @foreach ($module['items'] as $item)
                                @continue(! empty($item['permission']) && ! auth()->user()?->can($item['permission']))
                                <a href="{{ route($item['route']) }}"
                                    class="admin-module__link {{ request()->routeIs(...$item['active']) ? 'active' : '' }}">
                                    <span><i class="fa-solid {{ $item['icon'] }}"></i></span>
                                    <strong>{{ $item['label'] }}</strong>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endforeach
    </div>

    <div class="admin-sidebar-user d-flex align-items-center justify-content-between flex-shrink-0">
        @auth
        <div class="d-flex align-items-center overflow-hidden">
            <div class="user-avatar rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            <div class="ms-3 overflow-hidden" style="line-height: 1.2;">
                <p class="small fw-semibold text-white text-truncate mb-0">{{ Auth::user()->name }}</p>
                <p class="text-secondary text-truncate mb-0" style="font-size: 12px;">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-link p-1 text-secondary hover-danger" title="{{ __('Logout') }}" style="text-decoration: none;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </form>
        @endauth
    </div>

</nav>
