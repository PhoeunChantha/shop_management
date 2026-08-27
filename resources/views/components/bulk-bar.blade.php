@props([
    'destroy',           // route URL for bulk delete (DELETE)
    'status' => null,    // route URL for bulk enable/disable (PATCH) — omit if no status column
    'noun' => 'item',    // singular label used in the confirm copy
])

{{--
    Bulk action bar for admin tables. Must live inside an element with
    x-data="bulkSelect()" (see resources/js/app.js). Reads the shared `selected`
    array; row checkboxes carry `data-row-check` + value="{id}".
--}}
<div class="bulk-bar" x-show="count > 0" x-cloak>
    <span class="bulk-bar__count">
        <i class="fa-solid fa-check-double"></i>
        <span x-text="count"></span> selected
    </span>

    @isset($actions)
        {{ $actions }}
    @endisset

    @isset($status)
        <form method="POST" action="{{ $status }}" class="bulk-bar__form">
            @csrf
            @method('PATCH')
            <template x-for="id in selected" :key="'en-' + id"><input type="hidden" name="ids[]" :value="id"></template>
            <input type="hidden" name="status" value="1">
            <button type="submit" class="bulk-btn"><i class="fa-solid fa-eye"></i> Enable</button>
        </form>
        <form method="POST" action="{{ $status }}" class="bulk-bar__form">
            @csrf
            @method('PATCH')
            <template x-for="id in selected" :key="'dis-' + id"><input type="hidden" name="ids[]" :value="id"></template>
            <input type="hidden" name="status" value="0">
            <button type="submit" class="bulk-btn"><i class="fa-solid fa-eye-slash"></i> Disable</button>
        </form>
    @endisset

    <button type="button" class="bulk-btn bulk-btn--danger" @click="confirmingDelete = true">
        <i class="fa-solid fa-trash"></i> Delete
    </button>

    <button type="button" class="bulk-bar__clear" @click="clear()">
        <i class="fa-solid fa-xmark"></i> Clear
    </button>
</div>

{{-- Confirm modal — inside the Alpine root so it can read `selected`. --}}
<div class="modal-backdrop-premium" x-show="confirmingDelete" x-cloak style="display:none;"
    @keydown.escape.window="confirmingDelete = false" @click.self="confirmingDelete = false">
    <div class="delete-modal" role="dialog" aria-modal="true">
        <div class="delete-modal__icon-zone">
            <div class="delete-modal__icon-ring"><i class="fa-solid fa-trash-can"></i></div>
        </div>
        <div class="delete-modal__body">
            <h3>Delete selected {{ $noun }}s?</h3>
            <p>This will permanently remove <strong><span x-text="count"></span> {{ $noun }}(s)</strong>. Any that are still in use will be skipped.</p>
        </div>
        <div class="delete-modal__warning">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>This action cannot be undone</span>
        </div>
        <div class="delete-modal__actions">
            <button type="button" class="delete-modal__cancel" @click="confirmingDelete = false">Keep them</button>
            <form method="POST" action="{{ $destroy }}" class="mb-0 flex-1">
                @csrf
                @method('DELETE')
                <template x-for="id in selected" :key="'del-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                <button type="submit" class="delete-modal__confirm">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Yes, delete</span>
                </button>
            </form>
        </div>
    </div>
</div>
