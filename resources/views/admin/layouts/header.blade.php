<div class="container-fluid py-3 px-4 d-flex justify-content-between align-items-center gap-3">

    <div class="page-title fs-5 fw-bold text-dark min-w-0">
        {{ $header }}
    </div>

    <div class="d-flex align-items-center text-secondary small flex-shrink-0">

        <div class="language-switch d-none d-sm-inline-flex align-items-center p-1 rounded-pill me-3">
            <button class="btn btn-sm border-0 text-secondary fw-medium px-3 py-1 rounded-pill bg-transparent fs-7">
                KH
            </button>

            <button class="btn btn-sm border-0 fw-bold px-3 py-1 rounded-pill bg-white shadow-sm fs-7" style="color: #111827;">
                EN
            </button>
        </div>

        <button class="icon-button btn btn-link p-0 text-decoration-none fs-5 text-secondary me-2" type="button" aria-label="Toggle dark mode"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('admin-theme', dark ? 'dark' : 'light')"
            :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
            <i class="fa-regular" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
        </button>
        <button class="icon-button btn btn-link p-0 text-decoration-none fs-5 text-secondary me-3" type="button" aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
        </button>

        @auth
            <div class="d-flex align-items-center user-menu ps-3">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold text-uppercase"
                    style="width: 34px; height: 34px; background-color: #233653; font-size: 12px;">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <span class="fw-semibold text-dark ms-2 d-none d-sm-inline">
                    {{ Auth::user()->name }}
                </span>
            </div>
        @endauth

    </div>
</div>
