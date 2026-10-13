@props([
    'action',               // form action URL
    'label',                // trigger text
    'icon' => null,         // FontAwesome classes for the trigger
    'operation' => null,    // optional hidden `operation` field
    'method' => 'PATCH',
    'searchable' => false,  // show a filter box (long lists: categories, brands)
    'searchPlaceholder' => null,
])

{{--
    Bulk-bar dropdown action: one trigger button that opens a menu above the
    bar. Each item calls apply({field: value, ...}), which writes hidden inputs
    and submits the form for the selected rows. Must live inside x-data="bulkSelect()".

    <x-bulk-menu :action="route('...')" operation="status" :label="__('Status')" icon="fa-solid fa-toggle-on">
        <button type="button" class="bulk-menu__item" @click="apply({ status: 'active' })">Active</button>
    </x-bulk-menu>
--}}
<form method="POST" action="{{ $action }}" class="bulk-menu" x-data="bulkMenu()" :class="{ 'is-open': menuOpen }"
    @click.outside="close()" @keydown.escape.window="close()">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <template x-for="id in selected" :key="'bm-' + id"><input type="hidden" name="ids[]" :value="id"></template>
    @if ($operation)
        <input type="hidden" name="operation" value="{{ $operation }}">
    @endif

    <button type="button" class="bulk-btn bulk-menu__trigger" @click="toggle()" :aria-expanded="menuOpen" aria-haspopup="menu">
        @if ($icon)<i class="{{ $icon }}"></i>@endif
        <span>{{ $label }}</span>
        <i class="fa-solid fa-chevron-up bulk-menu__caret" aria-hidden="true"></i>
    </button>

    <div class="bulk-menu__panel" x-show="menuOpen" x-cloak role="menu"
        x-transition:enter="bulk-menu-enter" x-transition:enter-start="bulk-menu-from"
        x-transition:leave="bulk-menu-leave" x-transition:leave-end="bulk-menu-from">
        @if ($searchable)
            <label class="bulk-menu__search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="text" x-model="q" x-ref="search" placeholder="{{ $searchPlaceholder ?? __('Search…') }}" autocomplete="off">
            </label>
        @endif
        <div class="bulk-menu__list">
            {{ $slot }}
            @if ($searchable)
                <p class="bulk-menu__empty" x-show="!hasMatches" x-cloak>{{ __('No results') }}</p>
            @endif
        </div>
    </div>
</form>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('bulkMenu', () => ({
                menuOpen: false,
                q: '',
                toggle() {
                    this.menuOpen ? this.close() : this.openMenu();
                },
                openMenu() {
                    this.menuOpen = true;
                    this.$nextTick(() => this.$refs.search?.focus());
                },
                close() {
                    this.menuOpen = false;
                    this.q = '';
                },
                matches(label) {
                    return !this.q || String(label).toLowerCase().includes(this.q.trim().toLowerCase());
                },
                get hasMatches() {
                    if (!this.q) return true;
                    return Array.from(this.$root.querySelectorAll('[data-label]')).some((el) => this.matches(el.dataset.label));
                },
                apply(fields) {
                    const form = this.$root;
                    Object.entries(fields).forEach(([name, value]) => {
                        let input = form.querySelector(`input[type=hidden][name="${name}"][data-bulk-field]`);
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.dataset.bulkField = '';
                            form.appendChild(input);
                        }
                        input.value = value;
                    });
                    this.menuOpen = false;
                    form.requestSubmit();
                },
            }));
        });
    </script>
@endonce
