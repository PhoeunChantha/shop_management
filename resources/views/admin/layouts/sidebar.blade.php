<nav class="d-flex flex-column h-100 border-end shrink-0" style="width: 256px; 
 border-color: #334155 !important; background-color: #111827; color: #cbd5e1;">

    <div class="p-3 border-bottom flex-shrink-0 d-flex align-items-center" style="border-color: #334155 !important;">
        <div class="d-flex align-items-center justify-content-center text-white fw-bold fs-5 rounded" style="width: 36px; height: 36px; background-color: #334155; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
            <i class="fa-solid fa-shirt"></i>
        </div>
        <div class="ms-3">
            <h1 class="fw-bold text-white fs-6 mb-0 lh-sm">T-Shirt Shop</h1>
            <span class="text-secondary font-medium " style="font-size: 10px;">E-commerce System</span>
        </div>
    </div>

    <div class="flex-grow-1 p-2 overflow-auto" style="scrollbar-width: thin;">

        <div class="mb-2 mt-2">
            <!-- <p class="px-3 text-secondary text-uppercase fw-bold tracking-wider mb-2" style="font-size: 10px; letter-spacing: 0.05em;">Main</p> -->

            <div class="nav flex-column nav-pills">
                <a href="{{ route('dashboard') }}" class="nav-link d-flex align-items-center link-secondary">
                    <span class="me-3"><i class="fa-solid fa-home"></i></span>
                    <span class="small fw-medium">Dashboard</span>
                </a>

                <a href="{{ route('roles.index') }}" class="nav-link d-flex align-items-center link-secondary">
                    <span class="me-3"><i class="fa-solid fa-shield-halved"></i></span>
                    <span class="small fw-medium">Roles</span>
                </a>

                <a href="{{ route('permissions.index') }}" class="nav-link d-flex align-items-center link-secondary">
                    <span class="me-3"><i class="fa-solid fa-user-group"></i></span>
                    <span class="small fw-medium">Permissions</span>
                </a>


                <a href="{{ route('users.index') }}" class="nav-link d-flex align-items-center link-secondary">
                    <span class="me-3"><i class="fa-solid fa-users"></i></span>
                    <span class="small fw-medium">Users</span>
                </a>




            </div>
        </div>

    </div>

    <div class="p-3 border-top d-flex align-items-center justify-content-between flex-shrink-0" style="background-color: #111827; border-color: #334155 !important;">
        @auth
        <div class="d-flex align-items-center overflow-hidden">
            <div class="rounded-circle bg-purple text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 36px; height: 36px; background-color: #334155;">
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