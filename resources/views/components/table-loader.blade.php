@props([
    'label' => 'Loading…',
])

{{--
    <x-table-loader> — reusable loading overlay for admin data tables.

    Drop it in as the FIRST child of any `.premium-card` that holds a table
    toolbar / pagination. It listens for the navigations that reload the table
    (search, per-page, filters, pagination) and paints a blurred overlay with a
    top progress bar + spinner pill until the new page renders.

    Because every table refresh here is a real GET navigation, no extra JS wiring
    is needed — the overlay simply shows on submit and disappears on reload.

    Usage:
        <section class="premium-card">
            <x-table-loader />
            <x-table-toolbar> … </x-table-toolbar>
            <table class="premium-table"> … </table>
            <x-table-footer :paginator="$items" />
        </section>

    Opt any other link/button into the overlay with  data-table-loading.
--}}

@once
    <script>
        window.tableLoader = function () {
            return {
                loading: false,

                init() {
                    const show = () => { this.loading = true; };

                    // Search / per-page (toolbar-form) and the filter card submits.
                    document.addEventListener('submit', (e) => {
                        const f = e.target;
                        if (f instanceof HTMLFormElement &&
                            f.matches('form.toolbar-form, form.filter-card, form.table-toolbar')) {
                            show();
                        }
                    }, true);

                    // Pagination links + any opted-in control.
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('a[href]');
                        if (!link) return;
                        const inTable = link.closest('.table-footer') ||
                            link.hasAttribute('data-table-loading');
                        if (!inTable) return;
                        // Let modifier / new-tab clicks through without a fake load.
                        if (e.metaKey || e.ctrlKey || e.shiftKey || link.target === '_blank') return;
                        show();
                    });

                    // Clear when navigating away or restoring from the bfcache.
                    const hide = () => { this.loading = false; };
                    window.addEventListener('pagehide', hide);
                    window.addEventListener('pageshow', (e) => { if (e.persisted) hide(); });
                },
            };
        };
    </script>

    <style>
        /* Anchor the overlay to whichever card hosts a loader. */
        .premium-card:has(> .table-loader) { position: relative; }

        .table-loader {
            position: absolute;
            inset: 0;
            z-index: 30;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 130px;
            border-radius: inherit;
            background: color-mix(in srgb, #ffffff 60%, transparent);
            backdrop-filter: blur(3px) saturate(1.05);
            -webkit-backdrop-filter: blur(3px) saturate(1.05);
        }

        /* Indeterminate top progress bar. */
        .table-loader__bar {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            overflow: hidden;
            background: color-mix(in srgb, var(--primary-color, #101928) 12%, transparent);
        }
        .table-loader__bar::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0; left: -40%;
            width: 40%;
            border-radius: 3px;
            background: var(--primary-color, #101928);
            animation: table-loader-slide 1.1s cubic-bezier(.4, 0, .2, 1) infinite;
        }
        @keyframes table-loader-slide {
            0%   { left: -40%; }
            50%  { left: 35%; }
            100% { left: 100%; }
        }

        /* Centered spinner pill. */
        .table-loader__box {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 11px 18px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid var(--admin-line, #e5e7eb);
            box-shadow: 0 12px 32px rgba(16, 24, 40, 0.14), 0 2px 6px rgba(16, 24, 40, 0.06);
            font-size: 13.5px;
            font-weight: 600;
            color: var(--admin-ink, #101827);
            animation: table-loader-pop .28s cubic-bezier(.16, 1, .3, 1) both;
        }
        @keyframes table-loader-pop {
            from { opacity: 0; transform: translateY(-6px) scale(.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .table-loader__spinner {
            width: 16px; height: 16px;
            border: 2px solid color-mix(in srgb, var(--primary-color, #101928) 22%, transparent);
            border-top-color: var(--primary-color, #101928);
            border-radius: 50%;
            animation: table-loader-spin .6s linear infinite;
        }
        @keyframes table-loader-spin { to { transform: rotate(360deg); } }

        /* ---------- Dark mode ---------- */
        html.dark .table-loader {
            background: color-mix(in srgb, #0b1226 58%, transparent);
        }
        html.dark .table-loader__box {
            background: #0e1830;
            border-color: rgba(255, 255, 255, 0.10);
            color: #e6ecf7;
            box-shadow: 0 14px 36px rgba(0, 0, 0, 0.5);
        }

        @media (prefers-reduced-motion: reduce) {
            .table-loader__bar::before,
            .table-loader__spinner,
            .table-loader__box { animation-duration: .01ms; }
        }

        [x-cloak] { display: none !important; }
    </style>
@endonce

<div
    x-data="tableLoader()"
    x-show="loading"
    x-cloak
    x-transition.opacity.duration.150ms
    class="table-loader"
    role="status"
    aria-live="polite"
>
    <div class="table-loader__bar" aria-hidden="true"></div>
    <div class="table-loader__box">
        <span class="table-loader__spinner" aria-hidden="true"></span>
        <span>{{ $label }}</span>
    </div>
</div>
