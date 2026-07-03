@props([
    'label' => 'Actions',
    'width' => 184,
])

{{--
    Row actions dropdown for premium tables.

    The table wrapper uses overflow-x:auto (which forces overflow-y:auto), so the
    menu is `position: fixed` and positioned from the trigger's rect — that lets it
    escape the scroll container without being clipped. No teleport is used, so any
    JS bound to the slotted items (e.g. the delete-modal triggers) keeps working.
--}}
<div
    x-data="{
        open: false,
        top: 0,
        left: 0,
        position() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const menu = this.$refs.menu;
            const width = menu.offsetWidth || {{ (int) $width }};
            const height = menu.offsetHeight || 160;
            let left = Math.max(8, rect.right - width);
            left = Math.min(left, window.innerWidth - width - 8);
            let top = rect.bottom + 6;
            if (top + height > window.innerHeight - 8) {
                top = Math.max(8, rect.top - height - 6);
            }
            this.top = top;
            this.left = left;
        },
        toggle() {
            this.open = ! this.open;
            if (this.open) {
                this.$nextTick(() => this.position());
            }
        },
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
    {{ $attributes->merge(['class' => 'table-actions']) }}
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        :class="{ 'is-open': open }"
        class="table-actions__toggle"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
    >
        <i class="fa-solid fa-ellipsis-vertical"></i>
        <span class="sr-only">{{ $label }}</span>
    </button>

    <div
        x-ref="menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :style="`top: ${top}px; left: ${left}px; width: {{ (int) $width }}px;`"
        class="table-actions__menu"
        role="menu"
    >
        {{ $slot }}
    </div>
</div>
