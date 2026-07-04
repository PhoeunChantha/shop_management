<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Brands') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Brand table</p>
                <h3>All Brands</h3>
            </div>
            <button type="button" class="premium-button premium-button--dark" onclick="openBrandModal({ mode: 'create' })">
                <i class="fa-solid fa-plus"></i>
                <span>New Brand</span>
            </button>
        </div>

        <section class="premium-card">
            <x-table-loader />

            <form method="GET" action="{{ route('admin.brands.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-tags"></i>
                        <span>{{ $brands->total() }} result{{ $brands->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.requestSubmit()">
                            @foreach ([5, 10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search brands..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="width:80px;">Logo</th>
                            <th style="width:26%;">Brand Name</th>
                            <th>Slug</th>
                            <th style="width:110px;">Products</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($brands as $brand)
                        <tr>
                            <td>
                                <span class="muted-id">#{{ $brand->id }}</span>
                            </td>
                            <td>
                                @if ($brand->image)
                                    <img src="{{ Imageurl($brand->image, 'brands') }}" alt="{{ $brand->name }}"
                                        class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-gray-100 text-gray-300 dark:bg-white/10" style="width:40px;height:40px;"><i class="fa-solid fa-tag"></i></span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100">{{ $brand->name }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500 dark:text-slate-400 font-mono">{{ $brand->slug }}</span>
                            </td>
                            <td>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200 dark:bg-white/10 dark:text-slate-200 dark:border-white/10">
                                    {{ $brand->products_count }}
                                </span>
                            </td>
                            <td>
                                @if($brand->status)
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Enabled</span>
                                @else
                                <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <button type="button" class="table-action table-action--edit"
                                        data-action="{{ route('admin.brands.update', $brand->id) }}"
                                        data-name="{{ $brand->name }}"
                                        data-status="{{ $brand->status ? '1' : '0' }}"
                                        data-image="{{ $brand->image ? Imageurl($brand->image, 'brands') : '' }}"
                                        onclick="openBrandModal({ mode: 'edit', action: this.dataset.action, name: this.dataset.name, status: this.dataset.status, image: this.dataset.image || null })">
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Edit</span>
                                    </button>

                                    <button type="button" class="table-action table-action--delete"
                                        data-delete-modal-target="deleteBrandModal"
                                        data-delete-action="{{ route('admin.brands.destroy', $brand->id) }}"
                                        data-delete-name="{{ $brand->name }}">
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
                                    <i class="fa-solid fa-tags"></i>
                                    <strong>No brands found</strong>
                                    <span>Create your first brand or adjust the current search.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$brands" label="brands" />
        </section>

        <x-delete-confirm-modal
            id="deleteBrandModal"
            title="Delete this brand?"
            message-after="from the system. This cannot be undone." />

        @include('admin.brands._modal')
    </div>
</x-app-layout>
