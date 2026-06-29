<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Colors') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Color table</p>
                <h3>All Colors</h3>
            </div>
            <a href="{{ route('admin.colors.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Color</span>
            </a>
        </div>

        <section class="premium-card">
            <form method="GET" action="{{ route('admin.colors.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-palette"></i>
                        <span>{{ $colors->total() }} result{{ $colors->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search colors..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Color Name</th>
                            <th>Color Code (Hex)</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($colors as $color)
                        <tr>
                            <td>
                                <span class="muted-id">#{{ $color->id }}</span>
                            </td>
                            <td>
                                <div class="w-7 h-7 rounded-full border border-gray-300 shadow-sm dark:border-white/20" style="background-color: {{ $color->hex_code ?? '#FFFFFF' }};"></div>
                            </td>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100">{{ $color->name }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500 dark:text-slate-400 font-mono">{{ $color->code }}</span>
                            </td>
                            <td>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200 dark:bg-white/10 dark:text-slate-200 dark:border-white/10">
                                    {{ $color->sort_order }}
                                </span>
                            </td>
                            <td>
                                @if($color->status)
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Enabled</span>
                                @else
                                <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('admin.colors.edit', $color->id) }}" class="table-action table-action--edit">
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Edit</span>
                                    </a>

                                    <button type="button" class="table-action table-action--delete"
                                        data-delete-modal-target="deleteColorModal"
                                        data-delete-action="{{ route('admin.colors.destroy', $color->id) }}"
                                        data-delete-name="{{ $color->name }}">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fa-solid fa-palette"></i>
                                    <strong>No colors found</strong>
                                    <span>Try a different search term or clear the current search.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$colors" label="colors" />
        </section>

        <x-delete-confirm-modal
            id="deleteColorModal"
            title="Delete this color?"
            message-after="from the system. This cannot be undone." />
    </div>
</x-app-layout>