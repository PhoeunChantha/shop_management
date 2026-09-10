<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('New Customer Notification') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Compose') }}</p>
                <h3>{{ __('New Customer Notification') }}</h3>
            </div>
            <a href="{{ route('admin.customer-notifications.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <form action="{{ route('admin.customer-notifications.store') }}" method="POST"
            x-data="customerNotificationForm({
                previewUrl: '{{ route('admin.customer-notifications.preview-count') }}',
                csrf: '{{ csrf_token() }}',
                initialAudience: '{{ old('audience_type', 'all') }}',
            })"
            @submit="if (!confirmSend($event)) $event.preventDefault()">
            @csrf

            <section class="premium-card form-panel">
                <div class="form-panel-header">
                    <div class="form-panel-icon"><i class="fa-solid fa-pen"></i></div>
                    <div>
                        <p class="section-kicker">{{ __('Message') }}</p>
                        <h3>{{ __('What do you want to say?') }}</h3>
                    </div>
                </div>

                <div class="form-panel-body grid grid-cols-1 gap-4">
                    <div class="form-field">
                        <label for="title">{{ __('Title') }} <span class="text-red-500">*</span></label>
                        <input value="{{ old('title') }}" type="text" name="title" id="title"
                            class="form-input" maxlength="255" placeholder="{{ __('e.g. New arrivals are here 🎉') }}" required>
                        @error('title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="message">{{ __('Message') }} <span class="text-red-500">*</span></label>
                        <textarea name="message" id="message" class="form-input" rows="4" maxlength="2000"
                            placeholder="{{ __('Write the notification body...') }}" required>{{ old('message') }}</textarea>
                        @error('message')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="url">{{ __('Link') }}</label>
                        <input value="{{ old('url') }}" type="text" name="url" id="url"
                            class="form-input" placeholder="{{ __('e.g. /shop or https://…') }}">
                        <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ __('Optional — shown as a button in the email and a link in the in-app notification.') }}</small>
                        @error('url')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="premium-card form-panel mt-4">
                <div class="form-panel-header">
                    <div class="form-panel-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div>
                        <p class="section-kicker">{{ __('Audience') }}</p>
                        <h3>{{ __('Who should get this?') }}</h3>
                    </div>
                </div>

                <div class="form-panel-body grid grid-cols-1 gap-4">
                    <div class="form-field">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer">
                            <input type="radio" name="audience_type" value="all" x-model="audience">
                            {{ __('All customers') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;margin-top:8px">
                            <input type="radio" name="audience_type" value="segment" x-model="audience">
                            {{ __('Filtered segment') }}
                        </label>
                        @error('audience_type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="audience === 'segment'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="form-field">
                            <label for="search">{{ __('Search (name/email/phone)') }}</label>
                            <input type="text" name="search" id="search" class="form-input" x-model="search"
                                value="{{ old('search') }}" placeholder="{{ __('Optional') }}">
                        </div>
                        <x-select name="tag_id" label="{{ __('Tag') }}" size="sm" :value="old('tag_id')"
                            placeholder="{{ __('Any tag') }}" :options="$tags->pluck('name', 'id')->all()" />
                        <x-select name="spend" label="{{ __('Segment') }}" size="sm" :value="old('spend')"
                            placeholder="{{ __('Any') }}" :options="['new' => 'New (1 order)', 'repeat' => 'Repeat buyers', 'vip' => 'VIP ($500+)']" />
                    </div>

                    <div style="padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;display:flex;align-items:center;gap:10px">
                        <i class="fa-solid fa-users" style="color:#6b7280"></i>
                        <span>
                            <strong x-text="loading ? '…' : count"></strong>
                            {{ __('customer(s) will be notified by email; those with an account also get it in their notification inbox.') }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="form-panel-footer mt-4">
                <a href="{{ route('admin.customer-notifications.index') }}" class="form-cancel-button">{{ __('Cancel') }}</a>
                <button type="submit" class="form-submit-button">
                    <i class="fa-solid fa-paper-plane"></i>
                    {{ __('Send Notification') }}
                </button>
            </div>
        </form>
    </div>

    @once
        @push('js')
        <script>
            function customerNotificationForm({ previewUrl, csrf, initialAudience }) {
                return {
                    audience: initialAudience || 'all',
                    search: '{{ old('search', '') }}',
                    count: 0,
                    loading: false,
                    _timer: null,
                    init() {
                        this.refresh();
                        this.$watch('audience', () => this.refresh());
                        this.$watch('search', () => this.debouncedRefresh());
                        this.$el.querySelectorAll('select[name="tag_id"], select[name="spend"]').forEach((el) => {
                            el.addEventListener('change', () => this.refresh());
                        });
                    },
                    debouncedRefresh() {
                        clearTimeout(this._timer);
                        this._timer = setTimeout(() => this.refresh(), 400);
                    },
                    refresh() {
                        this.loading = true;
                        const form = this.$el;
                        const params = new URLSearchParams({
                            audience_type: this.audience,
                            search: form.querySelector('[name="search"]')?.value || '',
                            tag_id: form.querySelector('[name="tag_id"]')?.value || '',
                            spend: form.querySelector('[name="spend"]')?.value || '',
                        });

                        fetch(previewUrl + '?' + params.toString(), {
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        })
                            .then((r) => r.json())
                            .then((data) => { this.count = data.count ?? 0; })
                            .catch(() => {})
                            .finally(() => { this.loading = false; });
                    },
                    confirmSend(event) {
                        return window.confirm(
                            'Send this notification to ' + this.count + ' customer(s)? This cannot be undone.'
                        );
                    },
                };
            }
        </script>
        @endpush
    @endonce
</x-app-layout>
