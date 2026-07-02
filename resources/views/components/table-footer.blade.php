@props([
    'paginator',
    'label' => 'results',
])

<div {{ $attributes->merge(['class' => 'table-footer']) }}>
    <p>
        Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        {{ $label }}
    </p>

    <div class="pagination-shell">
        {{ $paginator->links('pagination::table') }}
    </div>
</div>
