@props([])

{{--
    Table card header toolbar.
    Slots:
      - left  : results count + per page selector (kept on the left)
      - right : search input (pushed to the right)
--}}
<div {{ $attributes->merge(['class' => 'table-toolbar']) }}>
    <div class="table-toolbar__left">
        {{ $left ?? $slot }}
    </div>

    @isset($right)
        <div class="table-toolbar__right">
            {{ $right }}
        </div>
    @endisset
</div>
