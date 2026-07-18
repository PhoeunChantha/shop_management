@php
    $modules = [
        [
            'type' => 'link',
            'label' => 'Dashboard',
            'caption' => 'Store overview',
            'icon' => 'fa-gauge-high',
            'route' => 'admin.dashboard',
            'routes' => ['admin.dashboard'],
        ],
        [
            'label' => 'Reports',
            'caption' => 'Analytics and exports',
            'icon' => 'fa-chart-column',
            'routes' => ['admin.reports.*'],
            'items' => [
                ['label' => 'Reports', 'icon' => 'fa-chart-column', 'route' => 'admin.reports.index', 'active' => ['admin.reports.*']],
            ],
        ],
        [
            'label' => 'Sales',
            'caption' => 'Orders and customers',
            'icon' => 'fa-receipt',
            'routes' => ['admin.orders.*', 'admin.customers.*', 'admin.returns.*', 'admin.abandoned-carts.*'],
            'items' => [
                ['label' => 'Orders', 'icon' => 'fa-list-check', 'route' => 'admin.orders.index', 'active' => ['admin.orders.*']],
                ['label' => 'Customers', 'icon' => 'fa-user-group', 'route' => 'admin.customers.index', 'active' => ['admin.customers.*']],
                ['label' => 'Returns & Refunds', 'icon' => 'fa-rotate-left', 'route' => 'admin.returns.index', 'active' => ['admin.returns.*']],
                ['label' => 'Abandoned Carts', 'icon' => 'fa-cart-arrow-down', 'route' => 'admin.abandoned-carts.index', 'active' => ['admin.abandoned-carts.*']],
            ],
        ],
        [
            'label' => 'Catalog',
            'caption' => 'Products and stock',
            'icon' => 'fa-store',
            'routes' => ['admin.products.*', 'admin.inventory.*', 'admin.suppliers.*', 'admin.purchase-orders.*', 'admin.reviews.*', 'admin.brands.*', 'admin.categories.*', 'admin.attributes.*', 'admin.sizes.*', 'admin.colors.*'],
            'items' => [
                ['label' => 'Products', 'icon' => 'fa-box-open', 'route' => 'admin.products.index', 'active' => ['admin.products.*']],
                ['label' => 'Inventory', 'icon' => 'fa-warehouse', 'route' => 'admin.inventory.index', 'active' => ['admin.inventory.index', 'admin.inventory.show', 'admin.inventory.adjust']],
                ['label' => 'Reorder Alerts', 'icon' => 'fa-truck-ramp-box', 'route' => 'admin.inventory.reorder', 'active' => ['admin.inventory.reorder', 'admin.inventory.reorder.*']],
                ['label' => 'Purchase Orders', 'icon' => 'fa-clipboard-list', 'route' => 'admin.purchase-orders.index', 'active' => ['admin.purchase-orders.*']],
                ['label' => 'Suppliers', 'icon' => 'fa-truck-field', 'route' => 'admin.suppliers.index', 'active' => ['admin.suppliers.*']],
                ['label' => 'Reviews', 'icon' => 'fa-star', 'route' => 'admin.reviews.index', 'active' => ['admin.reviews.*']],
                ['label' => 'Brands', 'icon' => 'fa-tags', 'route' => 'admin.brands.index', 'active' => ['admin.brands.*']],
                ['label' => 'Categories', 'icon' => 'fa-layer-group', 'route' => 'admin.categories.index', 'active' => ['admin.categories.*']],
                ['label' => 'Attributes', 'icon' => 'fa-sliders', 'route' => 'admin.attributes.index', 'active' => ['admin.attributes.*']],
                ['label' => 'Sizes', 'icon' => 'fa-ruler-combined', 'route' => 'admin.sizes.index', 'active' => ['admin.sizes.*']],
                ['label' => 'Colors', 'icon' => 'fa-palette', 'route' => 'admin.colors.index', 'active' => ['admin.colors.*']],
            ],
        ],
        [
            'label' => 'Marketing',
            'caption' => 'Campaign surfaces',
            'icon' => 'fa-bullhorn',
            'routes' => ['admin.coupons.*', 'admin.deals.*', 'admin.banners.*', 'admin.collections.*', 'admin.announcements.*', 'admin.media.*'],
            'items' => [
                ['label' => 'Coupons', 'icon' => 'fa-ticket', 'route' => 'admin.coupons.index', 'active' => ['admin.coupons.*']],
                ['label' => 'Offers & Deals', 'icon' => 'fa-tags', 'route' => 'admin.deals.index', 'active' => ['admin.deals.*']],
                ['label' => 'Banners', 'icon' => 'fa-images', 'route' => 'admin.banners.index', 'active' => ['admin.banners.*']],
                ['label' => 'Media Library', 'icon' => 'fa-photo-film', 'route' => 'admin.media.index', 'active' => ['admin.media.*']],
                ['label' => 'Collections', 'icon' => 'fa-layer-group', 'route' => 'admin.collections.index', 'active' => ['admin.collections.*']],
                ['label' => 'Announcement Bar', 'icon' => 'fa-bullhorn', 'route' => 'admin.announcements.index', 'active' => ['admin.announcements.*']],
            ],
        ],
        [
            'label' => 'Content',
            'caption' => 'Pages and SEO',
            'icon' => 'fa-file-lines',
            'routes' => ['admin.pages.*', 'admin.faqs.*', 'admin.seo.*'],
            'items' => [
                ['label' => 'Pages', 'icon' => 'fa-file-lines', 'route' => 'admin.pages.index', 'active' => ['admin.pages.*']],
                ['label' => 'SEO Manager', 'icon' => 'fa-magnifying-glass-chart', 'route' => 'admin.seo.index', 'active' => ['admin.seo.*']],
                ['label' => 'FAQ', 'icon' => 'fa-circle-question', 'route' => 'admin.faqs.index', 'active' => ['admin.faqs.*']],
            ],
        ],
        [
            'label' => 'Access',
            'caption' => 'Team permissions',
            'icon' => 'fa-shield-halved',
            'routes' => ['admin.users.*', 'admin.roles.*', 'admin.permissions.*', 'admin.permission-audit.*'],
            'items' => [
                ['label' => 'Users', 'icon' => 'fa-users', 'route' => 'admin.users.index', 'active' => ['admin.users.*']],
                ['label' => 'Roles', 'icon' => 'fa-user-shield', 'route' => 'admin.roles.index', 'active' => ['admin.roles.*']],
                ['label' => 'Permissions', 'icon' => 'fa-key', 'route' => 'admin.permissions.index', 'active' => ['admin.permissions.*']],
                ['label' => 'Permission Audit', 'icon' => 'fa-clipboard-check', 'route' => 'admin.permission-audit.index', 'active' => ['admin.permission-audit.*']],
            ],
        ],
        [
            'label' => 'Operations',
            'caption' => 'Store configuration',
            'icon' => 'fa-gear',
            'routes' => ['admin.setup-health.*', 'admin.shipping.*', 'admin.taxes.*', 'admin.saved-views.*', 'admin.notifications.*', 'admin.settings.*', 'admin.activity.*'],
            'items' => [
                ['label' => 'Setup Health', 'icon' => 'fa-list-check', 'route' => 'admin.setup-health.index', 'active' => ['admin.setup-health.*']],
                ['label' => 'Shipping Methods', 'icon' => 'fa-truck', 'route' => 'admin.shipping.index', 'active' => ['admin.shipping.*']],
                ['label' => 'Tax Rules', 'icon' => 'fa-percent', 'route' => 'admin.taxes.index', 'active' => ['admin.taxes.*']],
                ['label' => 'Saved Views', 'icon' => 'fa-bookmark', 'route' => 'admin.saved-views.index', 'active' => ['admin.saved-views.*']],
                ['label' => 'Notifications', 'icon' => 'fa-bell', 'route' => 'admin.notifications.index', 'active' => ['admin.notifications.*']],
                ['label' => 'Settings', 'icon' => 'fa-gear', 'route' => 'admin.settings.index', 'active' => ['admin.settings.*']],
                ['label' => 'Activity Log', 'icon' => 'fa-clock-rotate-left', 'route' => 'admin.activity.index', 'active' => ['admin.activity.*']],
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
                <span class="brand-kicker font-medium">Commerce Console</span>
            </div>
        </div>
        <div class="admin-brand-status">
            <span class="admin-brand-status-dot"></span>
            <span>Storefront live</span>
            <i class="fa-solid fa-arrow-trend-up ms-auto"></i>
        </div>
    </div>

    <div class="admin-nav-scroll flex-grow-1 overflow-auto">
        <p class="admin-nav-heading admin-nav-heading--module">Modules</p>

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
            <button type="submit" class="btn btn-link p-1 text-secondary hover-danger" title="Logout" style="text-decoration: none;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </form>
        @endauth
    </div>

</nav>
