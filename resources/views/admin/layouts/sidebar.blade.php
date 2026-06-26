<nav class="admin-sidebar d-flex flex-column h-100 shrink-0">

    <div class="admin-brand flex-shrink-0 d-flex align-items-center">
        <div class="brand-mark d-flex align-items-center justify-content-center text-white fw-bold fs-5">
            <i class="fa-solid fa-shirt"></i>
        </div>
        <div class="ms-3">
            <h1 class="fw-bold text-white fs-6 mb-0 lh-sm">T-Shirt Shop</h1>
            <span class="brand-kicker font-medium">E-commerce System</span>
        </div>
    </div>

    <div class="flex-grow-1 p-2 overflow-auto" style="scrollbar-width: thin;">

        <div class="mb-2 mt-2">
            <!-- <p class="px-3 text-secondary text-uppercase fw-bold tracking-wider mb-2" style="font-size: 10px; letter-spacing: 0.05em;">Main</p> -->

            <div class="nav flex-column nav-pills admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-home"></i></span>
                    <span class="small fw-medium">Dashboard</span>
                </a>
                <a href="{{ route('admin.users.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-users"></i></span>
                    <span class="small fw-medium">Users</span>
                </a>
                <a href="{{ route('admin.roles.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-shield-halved"></i></span>
                    <span class="small fw-medium">Roles</span>
                </a>

                <a href="{{ route('admin.permissions.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-user-group"></i></span>
                    <span class="small fw-medium">Permissions</span>
                </a>

                <a href="{{ route('admin.categories.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-layer-group"></i></span>
                    <span class="small fw-medium">Categories</span>
                </a>

                <a href="{{ route('admin.sizes.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.sizes.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-ruler-combined"></i></span>
                    <span class="small fw-medium">Sizes</span>
                </a>

                <a href="{{ route('admin.colors.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.colors.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-palette"></i></span>
                    <span class="small fw-medium">Colors</span>
                <a href="{{ route('admin.settings.index') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="me-3"><i class="fa-solid fa-gear"></i></span>
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