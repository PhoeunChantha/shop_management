
import 'bootstrap';
import $ from 'jquery';
import Alpine from 'alpinejs';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

window.$ = window.jQuery = $;
window.Alpine = Alpine;

toastr.options = {
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
    positionClass: 'toast-top-right',
    timeOut: 4000,
};
window.toastr = toastr;

// Reverb websockets (admin live chat). Loaded lazily and fully isolated: if the
// websocket stack fails to import or boot, every other admin behaviour in this
// bundle (AJAX forms/pages, tables, pickers) keeps working. No-op when the layout
// renders no reverb meta tags.
import('./echo')
    .then((echo) => {
        try {
            echo.bootEcho();
            echo.bootAdminChatFeed();
        } catch (error) {
            console.warn('Live chat websockets unavailable.', error);
        }
    })
    .catch((error) => console.warn('Live chat websockets unavailable.', error));

$(function () {
    // Mobile admin nav drawer (≤640px — see app.css): the topbar hamburger
    // (admin/layouts/header.blade.php) opens it by toggling
    // html.mobile-nav-open; these are the ways to close it again.
    $(document).on('click', '[data-mobile-nav-backdrop]', function () {
        document.documentElement.classList.remove('mobile-nav-open');
    });

    $(document).on('click', '.admin-sidebar .admin-module__link, .admin-sidebar .admin-module--link', function () {
        document.documentElement.classList.remove('mobile-nav-open');
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            document.documentElement.classList.remove('mobile-nav-open');
        }
    });

    $(window).on('resize', function () {
        if (window.innerWidth > 640) {
            document.documentElement.classList.remove('mobile-nav-open');
        }
    });

    let searchTimer;

    $(document).on('input', '[data-auto-search]', function () {
        const $input = $(this);
        const query = $.trim($input.val());

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            if (query.length === 0 || query.length >= 2) {
                const form = $input.closest('form').get(0);
                if (!form) return;
                // requestSubmit() actually navigates AND fires the `submit`
                // event, so <x-table-loader> can show its overlay.
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        }, 500);
    });

    // Date-range filter — daterangepicker + moment come from the CDN <script> tags
    // in the admin layout. They may finish loading just after DOMContentLoaded, so
    // wait until both are ready before initializing.
    let dateRangeSeq = 0;

    function initDateRanges(root) {
        const moment = window.moment;
        const format = 'MMMM D, YYYY';
        const label = (s, e) => s.format(format) + ' - ' + e.format(format);

        $(root || document).find('[data-daterange]').each(function () {
            const $input = $(this);
            // Idempotent: AJAX page swaps call this again for the fresh content only.
            if ($input.data('daterangepicker')) return;
            const index = dateRangeSeq++;
            const $form = $input.closest('form');
            const fromName = $input.data('daterangeFrom') || 'date_from';
            const toName = $input.data('daterangeTo') || 'date_to';
            const $from = $form.find(`input[name="${fromName}"]`);
            const $to = $form.find(`input[name="${toName}"]`);
            const start = $from.val() ? moment($from.val(), 'YYYY-MM-DD') : null;
            const end = $to.val() ? moment($to.val(), 'YYYY-MM-DD') : null;
            const namespace = `.daterange${index}`;
            let scrollFrame = null;

            $input.daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: { format: format, cancelLabel: 'Clear', separator: ' - ' },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                },
                ...(start && end ? { startDate: start, endDate: end } : {}),
            });

            // Show the picked range in the input (autoUpdateInput is off so it stays
            // empty until the admin actually chooses a range).
            if (start && end) {
                $input.val(label(start, end));
            }

            // Inputs flagged with `data-daterange-submit` reload their form as soon
            // as a range is applied or cleared (e.g. the dashboard period filter).
            const autoSubmit = $input.is('[data-daterange-submit]');
            const submitForm = () => {
                if (autoSubmit && $form.length) {
                    $form.get(0).requestSubmit();
                }
            };

            $input.on('apply.daterangepicker', function (ev, picker) {
                $input.val(label(picker.startDate, picker.endDate));
                $from.val(picker.startDate.format('YYYY-MM-DD'));
                $to.val(picker.endDate.format('YYYY-MM-DD'));
                submitForm();
            });

            $input.on('cancel.daterangepicker', function () {
                $input.val('');
                $from.val('');
                $to.val('');
                submitForm();
            });

            const syncPickerPosition = function () {
                if (scrollFrame) return;

                scrollFrame = window.requestAnimationFrame(function () {
                    scrollFrame = null;

                    const picker = $input.data('daterangepicker');
                    if (!picker || !picker.isShowing) return;

                    const rect = $input.get(0).getBoundingClientRect();
                    const hiddenAbove = rect.bottom < 80;
                    const hiddenBelow = rect.top > window.innerHeight - 40;

                    if (hiddenAbove || hiddenBelow) {
                        picker.hide();
                        return;
                    }

                    picker.move();
                });
            };

            $input.on('show.daterangepicker', function () {
                $('.admin-workspace').on(`scroll${namespace}`, syncPickerPosition);
                $(window).on(`resize${namespace}`, syncPickerPosition);
            });

            $input.on('hide.daterangepicker', function () {
                $('.admin-workspace').off(namespace);
                $(window).off(namespace);
            });
        });
    }

    function whenPickerReady(root, tries) {
        if (window.moment && $.fn.daterangepicker) {
            initDateRanges(root);
        } else if (tries < 60) {
            setTimeout(function () { whenPickerReady(root, tries + 1); }, 100);
        } else {
            console.error('Date-range picker failed to load from CDN.');
        }
    }

    if ($('[data-daterange]').length) {
        whenPickerReady(document, 0);
    }

    // Re-initialise pickers inside content swapped in by the AJAX page loader,
    // and drop the detached picker widgets (they live on <body>) before a swap.
    document.addEventListener('ajax:page-loaded', function (e) {
        whenPickerReady(e.detail.root, 0);
    });
    document.addEventListener('ajax:page-unload', function (e) {
        $(e.detail.root).find('[data-daterange]').each(function () {
            const picker = $(this).data('daterangepicker');
            if (picker) picker.remove();
        });
    });

    $('[data-avatar-input]').on('change', function () {
        const file = this.files && this.files[0];
        const $field = $(this).closest('.avatar-upload-field');
        const $preview = $field.find('[data-avatar-preview]');
        const $initial = $field.find('[data-avatar-initial]');
        const $filename = $field.find('[data-avatar-filename]');

        if (!file) {
            $filename.text('No file selected');
            return;
        }

        $filename.text(file.name);

        if (file.type && file.type.startsWith('image/')) {
            const previewUrl = URL.createObjectURL(file);

            $preview.attr('src', previewUrl).prop('hidden', false);
            $initial.prop('hidden', true);

            $preview.one('load', function () {
                URL.revokeObjectURL(previewUrl);
            });
        }
    });

    $('[data-permission-group-select]').each(function () {
        const groupToggle = this;
        const groupName = groupToggle.dataset.permissionGroupSelect;
        const $form = $(groupToggle).closest('form');
        const $permissions = $form.find(`[data-permission-group="${groupName}"]`);

        const syncGroupState = function () {
            const checkedCount = $permissions.filter(':checked').length;

            groupToggle.checked = checkedCount > 0 && checkedCount === $permissions.length;
            groupToggle.indeterminate = checkedCount > 0 && checkedCount < $permissions.length;
        };

        $(groupToggle).on('change', function () {
            $permissions.prop('checked', this.checked);
            this.indeterminate = false;
        });

        $permissions.on('change', syncGroupState);
        syncGroupState();
    });

    $('[data-add-permission-input]').on('click', function () {
        const $list = $('[data-permission-input-list]');
        const inputRow = `
            <div class="dynamic-input-row">
                <input type="text" name="names[]" class="form-input" placeholder="e.g. view users">
                <button type="button" class="dynamic-remove-button" data-remove-permission-input aria-label="Remove permission input">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;

        $list.append(inputRow);
        $list.find('input').last().trigger('focus');
    });

    $(document).on('click', '[data-remove-permission-input]', function () {
        const $rows = $('[data-permission-input-list] .dynamic-input-row');

        if ($rows.length === 1) {
            $(this).closest('.dynamic-input-row').find('input').val('').trigger('focus');
            return;
        }

        $(this).closest('.dynamic-input-row').remove();
    });
});

// Row-selection state for admin tables with bulk actions.
// Row checkboxes carry `data-row-check` and `value="{id}"`; the header
// select-all and the <x-bulk-bar> read/write this shared `selected` array.
Alpine.data('bulkSelect', () => ({
    selected: [],
    confirmingDelete: false,

    rowIds() {
        return Array.from(this.$root.querySelectorAll('input[data-row-check]')).map((b) => b.value);
    },
    get count() {
        return this.selected.length;
    },
    get allChecked() {
        const ids = this.rowIds();
        return ids.length > 0 && ids.every((id) => this.selected.includes(id));
    },
    get someChecked() {
        return this.count > 0 && !this.allChecked;
    },
    toggleAll(event) {
        this.selected = event.target.checked ? this.rowIds() : [];
    },
    clear() {
        this.selected = [];
        this.confirmingDelete = false;
    },
}));

Alpine.data('commandPalette', (url) => ({
    open: false,
    query: '',
    groups: [],
    loading: false,
    activeUrl: null,
    timer: null,

    init() {
        window.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.openPalette();
            }
        });
    },

    openPalette() {
        this.open = true;
        this.search();
        this.$nextTick(() => this.$refs.input?.focus());
    },

    closePalette() {
        this.open = false;
        this.activeUrl = null;
    },

    visibleGroups() {
        return this.groups.filter((group) => group.items && group.items.length);
    },

    flatItems() {
        return this.visibleGroups().flatMap((group) => group.items);
    },

    move(step) {
        const items = this.flatItems();
        if (!items.length) return;

        const index = Math.max(0, items.findIndex((item) => item.url === this.activeUrl));
        const next = (index + step + items.length) % items.length;
        this.activeUrl = items[next].url;
    },

    goSelected() {
        const target = this.activeUrl || this.flatItems()[0]?.url;
        if (target) window.location.href = target;
    },

    search() {
        clearTimeout(this.timer);
        this.timer = setTimeout(async () => {
            this.loading = true;

            try {
                const response = await fetch(`${url}?q=${encodeURIComponent(this.query)}`, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();
                this.groups = payload.groups || [];
                this.activeUrl = this.flatItems()[0]?.url || null;
            } catch (error) {
                console.error('Command palette search failed.', error);
                this.groups = [];
                this.activeUrl = null;
            } finally {
                this.loading = false;
            }
        }, 120);
    },
}));

/**
 * AJAX form submit for any <form data-ajax-form> (e.g. admin settings tabs).
 * Keeps the page from reloading, validates [data-required] fields with a toastr
 * alert, sends multipart FormData (so file uploads + method spoofing survive),
 * and surfaces server 422 errors inline + on the tab that owns the first error.
 */
(function () {
    // Convert a Laravel error key (payment_methods.0.name) to an input name
    // (payment_methods[0][name]) so we can locate the field in the DOM.
    function errorKeyToName(key) {
        const parts = key.split('.');
        return parts[0] + parts.slice(1).map((p) => `[${p}]`).join('');
    }

    // Switch to the settings tab that contains the given element, if any.
    function activateTabFor(el) {
        const panel = el.closest('[data-tab-panel]');
        if (!panel) return;
        const key = panel.getAttribute('data-tab-panel');
        const navBtn = document.querySelector(`[data-tab="${key}"]`);
        if (navBtn) navBtn.click();
    }

    function clearErrors(form) {
        form.querySelectorAll('.ajax-error').forEach((el) => el.remove());
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    }

    function markError(input, message) {
        input.classList.add('is-invalid');
        if (message) {
            const p = document.createElement('p');
            p.className = 'ajax-error text-red-500 text-sm mt-1.5';
            p.textContent = message;
            (input.closest('.form-field') || input.parentNode).appendChild(p);
        }
    }

    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax-form')) return;

        e.preventDefault();
        clearErrors(form);

        // Client-side required check — data-required avoids native validation
        // choking on fields inside hidden (display:none) tab panels.
        const missing = [];
        form.querySelectorAll('[data-required]').forEach((el) => {
            if (el.disabled) return;
            if (!String(el.value ?? '').trim()) missing.push(el);
        });

        if (missing.length) {
            missing.forEach((el) => markError(el, ''));
            const first = missing[0];
            activateTabFor(first);
            const label = first.getAttribute('data-required-label');
            window.toastr?.error(label ? `${label} is required.` : 'Please fill in all required fields.');
            setTimeout(() => first.focus(), 60);
            return;
        }

        const button = form.querySelector('[type="submit"]');
        const originalHtml = button ? button.innerHTML : '';
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + (button.dataset.loadingText || 'Saving…');
        }

        try {
            const res = await fetch(form.action, {
                method: 'POST', // real verb comes from the _method field inside FormData
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: new FormData(form),
            });

            if (res.ok) {
                const data = await res.json().catch(() => ({}));
                window.toastr?.success(data.message || 'Saved successfully.');
            } else if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                const errors = data.errors || {};
                let firstInput = null;
                Object.keys(errors).forEach((key) => {
                    const name = errorKeyToName(key);
                    const input = form.querySelector(`[name="${name}"]`);
                    if (input) {
                        markError(input, errors[key][0]);
                        if (!firstInput) firstInput = input;
                    }
                });
                if (firstInput) {
                    activateTabFor(firstInput);
                    setTimeout(() => firstInput.focus(), 60);
                }
                window.toastr?.error(data.message || 'Please fix the highlighted fields.');
            } else {
                window.toastr?.error('Something went wrong. Please try again.');
            }
        } catch (error) {
            console.error('Settings save failed.', error);
            window.toastr?.error('Network error. Please try again.');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        }
    });
})();

/**
 * AJAX tables — any <x-admin.table-card ajax>. Search, per-page and pagination
 * update the table in place (no full page reload). Only the [data-ajax-region]
 * (table + footer) is swapped, so the toolbar's focused search input survives.
 */
(function () {
    function containers() {
        return Array.from(document.querySelectorAll('[data-ajax-table]'));
    }

    async function loadInto(container, url) {
        const region = container.querySelector('[data-ajax-region]');
        if (!region) return;

        region.classList.add('is-loading');

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            // Match the same table by its position among all AJAX tables on the page.
            const index = containers().indexOf(container);
            const fresh = doc.querySelectorAll('[data-ajax-table]')[index];
            const freshRegion = fresh ? fresh.querySelector('[data-ajax-region]') : null;

            if (freshRegion) {
                region.innerHTML = freshRegion.innerHTML;
                // Re-hydrate Alpine bindings inside the swapped rows (e.g. bulk-select
                // checkboxes) so interactive tables keep working after a fetch.
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(region);
                }
                window.history.pushState({ ajaxTable: true }, '', url);
            } else {
                window.location.assign(url); // structure changed — fall back to a real load
            }
        } catch (error) {
            console.error('AJAX table load failed.', error);
            window.toastr?.error('Could not load results. Please try again.');
        } finally {
            region.classList.remove('is-loading');
        }
    }

    function urlFromForm(form) {
        const params = new URLSearchParams(new FormData(form));
        const base = (form.getAttribute('action') || window.location.pathname).split('?')[0];
        const query = params.toString();

        return query ? `${base}?${query}` : base;
    }

    // Search + per-page forms only (class 'toolbar-form'). POST action/delete
    // forms inside a table are left alone so they submit normally.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('toolbar-form')) return;
        const container = form.closest('[data-ajax-table]');
        if (!container) return;

        e.preventDefault();
        loadInto(container, urlFromForm(form));
    }, true);

    // Pagination links inside the swapped region.
    document.addEventListener('click', function (e) {
        const link = e.target.closest('[data-ajax-table] .table-footer a[href], [data-ajax-table] .pager a[href]');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || link.target === '_blank') return;

        const container = link.closest('[data-ajax-table]');
        if (!container) return;

        e.preventDefault();
        loadInto(container, link.href);
    });

    // Sortable column headers inside the swapped region.
    document.addEventListener('click', function (e) {
        const link = e.target.closest('[data-ajax-table] [data-ajax-region] a.th-sort[href]');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || link.target === '_blank') return;

        const container = link.closest('[data-ajax-table]');
        if (!container) return;

        e.preventDefault();
        loadInto(container, link.href);
    });

    // Keep the Back/Forward buttons working by re-syncing each table. Tables that
    // sit inside an AJAX page are refreshed by the page loader instead.
    window.addEventListener('popstate', function () {
        containers()
            .filter((container) => !container.closest('[data-ajax-page]'))
            .forEach((container) => loadInto(container, window.location.href));
    });
})();

/**
 * AJAX pages — global, opt-in filtering without a full reload.
 *
 *   <div data-ajax-page> … </div>            the region that is swapped
 *   <form method="GET" data-ajax-filter>     filter forms inside it
 *   <a href="…" data-ajax-link>              links (Reset, presets) inside it
 *
 * The page is fetched with the new query string, the fresh [data-ajax-page]
 * content replaces the current one, the URL is pushed to history, Alpine is
 * re-hydrated, and two events fire on `document`:
 *
 *   ajax:page-unload  { root }        before the swap (tear down charts/pickers)
 *   ajax:page-loaded  { root, url }   after the swap (re-init charts/pickers)
 *
 * Only GET forms are intercepted; anything else submits normally.
 */
(function () {
    const SELECTOR = '[data-ajax-page]';
    let inflight = null;

    function regions() {
        return Array.from(document.querySelectorAll(SELECTOR));
    }

    function urlFromForm(form) {
        const params = new URLSearchParams(new FormData(form));
        // Reset paging whenever the filter set changes.
        params.delete('page');
        const base = (form.getAttribute('action') || window.location.pathname).split('?')[0];
        const query = params.toString();

        return query ? `${base}?${query}` : base;
    }

    async function loadPage(container, url, { push = true } = {}) {
        if (inflight) inflight.abort();
        const controller = new AbortController();
        inflight = controller;

        container.classList.add('is-loading');
        container.setAttribute('aria-busy', 'true');

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            // Regions may nest (layout <main> + a page-level region); match the
            // same one by its position in document order.
            const fresh = doc.querySelectorAll(SELECTOR)[regions().indexOf(container)];

            if (!fresh) {
                window.location.assign(url); // structure changed — fall back to a real load
                return;
            }

            document.dispatchEvent(new CustomEvent('ajax:page-unload', { detail: { root: container } }));
            if (window.Alpine && typeof window.Alpine.destroyTree === 'function') {
                window.Alpine.destroyTree(container);
            }

            container.innerHTML = fresh.innerHTML;

            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(container);
            }
            if (push) {
                window.history.pushState({ ajaxPage: true }, '', url);
            }

            document.dispatchEvent(new CustomEvent('ajax:page-loaded', { detail: { root: container, url } }));
        } catch (error) {
            if (error && error.name === 'AbortError') return;
            console.error('AJAX page load failed.', error);
            window.toastr?.error('Could not load results. Please try again.');
            document.dispatchEvent(new CustomEvent('ajax:page-error', { detail: { root: container, url, error } }));
        } finally {
            if (inflight === controller) inflight = null;
            container.classList.remove('is-loading');
            container.removeAttribute('aria-busy');
        }
    }

    // Which GET forms are page filters: anything flagged data-ajax-filter, plus
    // the shared filter card and the search / per-page toolbar forms — unless
    // they belong to an AJAX table, which refreshes itself.
    function isPageFilter(form) {
        if (!(form instanceof HTMLFormElement)) return false;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return false;
        if (form.hasAttribute('data-ajax-filter')) return true;
        if (form.closest('[data-ajax-table]')) return false;

        return form.matches('form.filter-card, form.toolbar-form');
    }

    // Which links refresh the page in place: anything flagged data-ajax-link,
    // plus pagination and sortable column headers outside AJAX tables.
    function isPageLink(link) {
        if (link.hasAttribute('data-ajax-link')) return true;
        if (link.closest('[data-ajax-table]')) return false;

        return !!link.closest('.table-footer, .pager') || link.matches('a.th-sort');
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!isPageFilter(form)) return;
        const container = form.closest(SELECTOR);
        if (!container) return;

        e.preventDefault();
        loadPage(container, urlFromForm(form));
    }, true);

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link || !isPageLink(link)) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || link.target === '_blank' || link.hasAttribute('download')) return;
        const href = link.getAttribute('href') || '';
        if (href.startsWith('#') || href.startsWith('javascript:')) return;
        const container = link.closest(SELECTOR);
        if (!container) return;
        // Only same-path links are swapped in place; anything else is a real navigation.
        const target = new URL(link.href, window.location.href);
        if (target.origin !== window.location.origin || target.pathname !== window.location.pathname) return;

        e.preventDefault();
        loadPage(container, link.href);
    });

    // Back / Forward — refresh the outermost region from the restored URL.
    window.addEventListener('popstate', function () {
        const container = document.querySelector(SELECTOR);
        if (container) loadPage(container, window.location.href, { push: false });
    });
})();

Alpine.start();
