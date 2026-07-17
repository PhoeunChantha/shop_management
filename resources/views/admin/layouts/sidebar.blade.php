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

    <div class="admin-nav-scroll flex-grow-1 p-2 overflow-auto">

        {{-- General --}}
        <div class="admin-nav-section">
            <p class="admin-nav-heading">General</p>
            <div class="nav flex-column admin-nav">
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-gauge-high"></i></span>
                    <span class="small fw-medium">Dashboard</span>
                </a>
            </div>
        </div>

        {{-- Sales --}}
        @php($salesActive = request()->routeIs('admin.orders.*', 'admin.customers.*', 'admin.returns.*', 'admin.abandoned-carts.*'))
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Sales</p>
            <div class="nav flex-column admin-nav">
                <div class="admin-nav-group" x-data="{ open: {{ request()->routeIs('admin.orders.*', 'admin.returns.*') ? 'true' : 'false' }} }"
                    :class="{ 'is-open': open }">
                    <button type="button" class="nav-link admin-nav-toggle d-flex align-items-center {{ request()->routeIs('admin.orders.*', 'admin.returns.*') ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="nav-ico"><i class="fa-solid fa-receipt"></i></span>
                        <span class="small fw-medium flex-grow-1 text-start">Orders</span>
                        <i class="fa-solid fa-chevron-down admin-nav-caret"></i>
                    </button>
                    <div class="admin-nav-sub">
                        <div class="admin-nav-sub-inner">
                            <a href="{{ route('admin.orders.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-list-check"></i></span>
                                <span class="small fw-medium">All Orders</span>
                            </a>
                            <a href="{{ route('admin.returns.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-rotate-left"></i></span>
                                <span class="small fw-medium">Returns & Refunds</span>
                            </a>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.customers.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-user-group"></i></span>
                    <span class="small fw-medium">Customers</span>
                </a>
                <a href="{{ route('admin.abandoned-carts.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.abandoned-carts.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-cart-arrow-down"></i></span>
                    <span class="small fw-medium">Abandoned Carts</span>
                </a>
            </div>
        </div>

        {{-- Catalog (collapsible) --}}
        @php($catalogActive = request()->routeIs('admin.products.*', 'admin.inventory.*', 'admin.suppliers.*', 'admin.purchase-orders.*', 'admin.reviews.*', 'admin.brands.*', 'admin.categories.*', 'admin.attributes.*', 'admin.sizes.*', 'admin.colors.*'))
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Catalog</p>
            <div class="nav flex-column admin-nav">
                <div class="admin-nav-group" x-data="{ open: {{ $catalogActive ? 'true' : 'false' }} }"
                    :class="{ 'is-open': open }">
                    <button type="button" class="nav-link admin-nav-toggle d-flex align-items-center {{ $catalogActive ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="nav-ico"><i class="fa-solid fa-store"></i></span>
                        <span class="small fw-medium flex-grow-1 text-start">Catalog</span>
                        <i class="fa-solid fa-chevron-down admin-nav-caret"></i>
                    </button>
                    <div class="admin-nav-sub">
                        <div class="admin-nav-sub-inner">
                            <a href="{{ route('admin.products.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-box-open"></i></span>
                                <span class="small fw-medium">Products</span>
                            </a>
                            <a href="{{ route('admin.inventory.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-warehouse"></i></span>
                                <span class="small fw-medium">Inventory</span>
                            </a>
                            <a href="{{ route('admin.purchase-orders.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-clipboard-list"></i></span>
                                <span class="small fw-medium">Purchase Orders</span>
                            </a>
                            <a href="{{ route('admin.suppliers.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-truck-field"></i></span>
                                <span class="small fw-medium">Suppliers</span>
                            </a>
                            <a href="{{ route('admin.reviews.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-star"></i></span>
                                <span class="small fw-medium">Reviews</span>
                            </a>
                            <a href="{{ route('admin.brands.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-tags"></i></span>
                                <span class="small fw-medium">Brands</span>
                            </a>
                            <a href="{{ route('admin.categories.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-layer-group"></i></span>
                                <span class="small fw-medium">Categories</span>
                            </a>
                            <a href="{{ route('admin.attributes.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-sliders"></i></span>
                                <span class="small fw-medium">Attributes</span>
                            </a>
                            <a href="{{ route('admin.sizes.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.sizes.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-ruler-combined"></i></span>
                                <span class="small fw-medium">Sizes</span>
                            </a>
                            <a href="{{ route('admin.colors.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.colors.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-palette"></i></span>
                                <span class="small fw-medium">Colors</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Access Control (collapsible) --}}
        @php($accessActive = request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.permissions.*', 'admin.permission-audit.*'))
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Access Control</p>
            <div class="nav flex-column admin-nav">
                <div class="admin-nav-group" x-data="{ open: {{ $accessActive ? 'true' : 'false' }} }"
                    :class="{ 'is-open': open }">
                    <button type="button" class="nav-link admin-nav-toggle d-flex align-items-center {{ $accessActive ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="nav-ico"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="small fw-medium flex-grow-1 text-start">Access Control</span>
                        <i class="fa-solid fa-chevron-down admin-nav-caret"></i>
                    </button>
                    <div class="admin-nav-sub">
                        <div class="admin-nav-sub-inner">
                            <a href="{{ route('admin.users.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-users"></i></span>
                                <span class="small fw-medium">Users</span>
                            </a>
                            <a href="{{ route('admin.roles.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-user-shield"></i></span>
                                <span class="small fw-medium">Roles</span>
                            </a>
                            <a href="{{ route('admin.permissions.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-key"></i></span>
                                <span class="small fw-medium">Permissions</span>
                            </a>
                            <a href="{{ route('admin.permission-audit.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.permission-audit.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-clipboard-check"></i></span>
                                <span class="small fw-medium">Permission Audit</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Marketing (collapsible) --}}
        @php($marketingActive = request()->routeIs('admin.coupons.*', 'admin.deals.*', 'admin.banners.*', 'admin.collections.*', 'admin.announcements.*', 'admin.media.*'))
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Marketing</p>
            <div class="nav flex-column admin-nav">
                <div class="admin-nav-group" x-data="{ open: {{ $marketingActive ? 'true' : 'false' }} }"
                    :class="{ 'is-open': open }">
                    <button type="button" class="nav-link admin-nav-toggle d-flex align-items-center {{ $marketingActive ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="nav-ico"><i class="fa-solid fa-bullhorn"></i></span>
                        <span class="small fw-medium flex-grow-1 text-start">Marketing</span>
                        <i class="fa-solid fa-chevron-down admin-nav-caret"></i>
                    </button>
                    <div class="admin-nav-sub">
                        <div class="admin-nav-sub-inner">
                            <a href="{{ route('admin.coupons.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-ticket"></i></span>
                                <span class="small fw-medium">Coupons</span>
                            </a>
                            <a href="{{ route('admin.deals.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.deals.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-tags"></i></span>
                                <span class="small fw-medium">Offers & Deals</span>
                            </a>
                            <a href="{{ route('admin.banners.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-images"></i></span>
                                <span class="small fw-medium">Banners</span>
                            </a>
                            <a href="{{ route('admin.media.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-photo-film"></i></span>
                                <span class="small fw-medium">Media Library</span>
                            </a>
                            <a href="{{ route('admin.collections.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.collections.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-layer-group"></i></span>
                                <span class="small fw-medium">Collections</span>
                            </a>
                            <a href="{{ route('admin.announcements.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-bullhorn"></i></span>
                                <span class="small fw-medium">Announcement bar</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content (collapsible) --}}
        @php($contentActive = request()->routeIs('admin.pages.*', 'admin.faqs.*', 'admin.seo.*'))
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Content</p>
            <div class="nav flex-column admin-nav">
                <div class="admin-nav-group" x-data="{ open: {{ $contentActive ? 'true' : 'false' }} }"
                    :class="{ 'is-open': open }">
                    <button type="button" class="nav-link admin-nav-toggle d-flex align-items-center {{ $contentActive ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="nav-ico"><i class="fa-solid fa-file-lines"></i></span>
                        <span class="small fw-medium flex-grow-1 text-start">Content</span>
                        <i class="fa-solid fa-chevron-down admin-nav-caret"></i>
                    </button>
                    <div class="admin-nav-sub">
                        <div class="admin-nav-sub-inner">
                            <a href="{{ route('admin.pages.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-file-lines"></i></span>
                                <span class="small fw-medium">Pages</span>
                            </a>
                            <a href="{{ route('admin.seo.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                                <span class="small fw-medium">SEO Manager</span>
                            </a>
                            <a href="{{ route('admin.faqs.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-circle-question"></i></span>
                                <span class="small fw-medium">FAQ</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuration (collapsible) --}}
        @php($configActive = request()->routeIs('admin.shipping.*', 'admin.taxes.*'))
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Configuration</p>
            <div class="nav flex-column admin-nav">
                <div class="admin-nav-group" x-data="{ open: {{ $configActive ? 'true' : 'false' }} }"
                    :class="{ 'is-open': open }">
                    <button type="button" class="nav-link admin-nav-toggle d-flex align-items-center {{ $configActive ? 'has-active' : '' }}"
                        @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                        <span class="nav-ico"><i class="fa-solid fa-truck-fast"></i></span>
                        <span class="small fw-medium flex-grow-1 text-start">Store setup</span>
                        <i class="fa-solid fa-chevron-down admin-nav-caret"></i>
                    </button>
                    <div class="admin-nav-sub">
                        <div class="admin-nav-sub-inner">
                            <a href="{{ route('admin.shipping.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.shipping.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-truck"></i></span>
                                <span class="small fw-medium">Shipping methods</span>
                            </a>
                            <a href="{{ route('admin.taxes.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.taxes.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-percent"></i></span>
                                <span class="small fw-medium">Tax rules</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- System --}}
        <div class="admin-nav-section">
            <p class="admin-nav-heading">System</p>
            <div class="nav flex-column admin-nav">
                <a href="{{ route('admin.notifications.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-bell"></i></span>
                    <span class="small fw-medium">Notifications</span>
                </a>
                <a href="{{ route('admin.settings.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-gear"></i></span>
                    <span class="small fw-medium">Settings</span>
                </a>
                <a href="{{ route('admin.activity.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.activity.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <span class="small fw-medium">Activity log</span>
                </a>
            </div>
        </div>

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
