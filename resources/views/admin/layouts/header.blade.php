<div class="container-fluid py-3 px-4 d-flex justify-content-between align-items-center bg-white">

    <div class="fs-5 fw-bold text-dark">
        {{ $header }}
    </div>

    <div class="d-flex align-items-center text-secondary small">

        <div class="d-inline-flex align-items-center bg-light p-1 rounded-pill me-3" style="background-color: #f1f3f5 !important;">
            <button class="btn btn-sm border-0 text-secondary fw-medium px-3 py-1 rounded-pill bg-transparent fs-7">
                ខ្មែរ
            </button>

            <button class="btn btn-sm border-0 fw-bold px-3 py-1 rounded-pill bg-white shadow-sm fs-7" style=" color: #111827;">
                EN
            </button>
        </div>

        <button class="btn btn-link p-0 text-decoration-none fs-5 text-secondary hover-dark me-3">
            <i class="fa-regular fa-moon"></i>
        </button>
        <button class="btn btn-link p-0 text-decoration-none fs-5 text-secondary hover-dark me-3">
            <i class="fa-regular fa-bell"></i>
        </button>

        @auth
            <div class="d-flex align-items-center border-start ps-3" style="border-color: #e2e8f0 !important;">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold text-uppercase"
                    style="width: 32px; height: 32px; background-color: #334155; font-size: 12px;">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <span class="fw-medium text-dark ms-2 d-none d-sm-inline">
                    {{ Auth::user()->name }}
                </span>
            </div>
        @endauth

    </div>
</div>