<nav class="admin-sidebar d-flex flex-column h-100 shrink-0">

    <div class="admin-brand flex-shrink-0 d-flex align-items-center">
        @if (!empty($adminLogo))
            <img src="{{ $adminLogo }}" alt="{{ $adminSiteName ?? 'Logo' }}" class="admin-brand-logo">
        @else
            <div class="brand-mark d-flex align-items-center justify-content-center text-white fw-bold fs-5">
                <i class="fa-solid fa-shirt"></i>
            </div>
        @endif
        <div class="ms-3">
            <h1 class="fw-bold text-white fs-6 mb-0 lh-sm">{{ $adminSiteName ?? 'T-Shirt Shop' }}</h1>
            <span class="brand-kicker font-medium">E-commerce System</span>
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
        <div class="admin-nav-section">
            <p class="admin-nav-heading">Sales</p>
            <div class="nav flex-column admin-nav">
                <a href="{{ route('admin.orders.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-receipt"></i></span>
                    <span class="small fw-medium">Orders</span>
                </a>
            </div>
        </div>

        {{-- Catalog (collapsible) --}}
        @php($catalogActive = request()->routeIs('admin.products.*', 'admin.inventory.*', 'admin.brands.*', 'admin.categories.*', 'admin.attributes.*', 'admin.sizes.*', 'admin.colors.*'))
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
        @php($accessActive = request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.permissions.*'))
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Marketing (collapsible) --}}
        @php($marketingActive = request()->routeIs('admin.coupons.*', 'admin.banners.*', 'admin.collections.*', 'admin.announcements.*'))
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
                            <a href="{{ route('admin.banners.index') }}"
                                class="nav-link nav-sublink d-flex align-items-center {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                                <span class="nav-ico"><i class="fa-solid fa-images"></i></span>
                                <span class="small fw-medium">Banners</span>
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
                <a href="{{ route('admin.settings.index') }}"
                    class="nav-link d-flex align-items-center {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-gear"></i></span>
                    <span class="small fw-medium">Settings</span>
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
