@php
    // Reopen after a failed validation (the request redirects back with old input).
    $reopen = $errors->any() && old('form_mode') === 'wallet-adjust';
@endphp

<div
    class="modal-backdrop-premium"
    x-data="walletAdjustModal({
        customers: @js($customerOptions),
        reopen: {{ $reopen ? 'true' : 'false' }},
        old: {
            user_id: @js(old('user_id')),
            direction: @js(old('direction', 'credit')),
            amount: @js(old('amount', '')),
            note: @js(old('note', '')),
        },
    })"
    x-show="open"
    x-cloak
    x-transition.opacity.duration.150ms
    @keydown.escape.window="close()"
    @wallet-adjust.window="show($event.detail)"
    @click.self="close()"
    style="display:none;"
>
    <div class="form-modal wallet-adjust-modal"
        role="dialog" aria-modal="true" aria-labelledby="walletAdjustTitle"
        x-show="open"
        x-transition:enter="fm-enter"
        x-transition:enter-start="fm-from"
        x-transition:enter-end="fm-to"
        x-transition:leave="fm-leave"
        x-transition:leave-start="fm-to"
        x-transition:leave-end="fm-from">
        <div class="form-modal__head">
            <div class="form-modal__icon"><i class="fa-solid fa-plus-minus"></i></div>
            <div class="flex-grow-1">
                <h3 id="walletAdjustTitle">{{ __('Adjust wallet balance') }}</h3>
                <p>{{ __('Credit or debit a customer’s store wallet. Every change is logged.') }}</p>
            </div>
            <button type="button" class="form-modal__close" @click="close()" aria-label="{{ __('Close') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.wallets.adjust') }}" method="POST" class="form-modal__body" @submit="submitting = true">
            @csrf
            <input type="hidden" name="form_mode" value="wallet-adjust">
            <input type="hidden" name="user_id" :value="selected ? selected.id : ''">

            {{-- customer --}}
            <div class="form-field">
                <label for="wallet_adjust_search">{{ __('Customer') }} <span class="text-red-500">*</span></label>

                <template x-if="selected">
                    <div class="wa-picked">
                        <span class="wa-avatar" x-text="initials(selected.name)"></span>
                        <span class="wa-picked__who">
                            <strong x-text="selected.name"></strong>
                            <small x-text="selected.email"></small>
                        </span>
                        <span class="wa-picked__bal" x-text="money(selected.balance)"></span>
                        <button type="button" class="wa-change" @click="clearCustomer()">{{ __('Change') }}</button>
                    </div>
                </template>

                <div x-show="!selected">
                    <div class="wa-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="wallet_adjust_search" x-ref="search" x-model="query" class="form-input"
                            placeholder="{{ __('Search name or email…') }}" autocomplete="off"
                            @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                            @keydown.enter.prevent="pick(results[cursor])">
                    </div>
                    <ul class="wa-results" role="listbox" aria-label="{{ __('Customers') }}">
                        <template x-for="(c, i) in results" :key="c.id">
                            <li role="option" :aria-selected="i === cursor" :class="{ 'is-active': i === cursor }"
                                @click="pick(c)" @mouseenter="cursor = i">
                                <span class="wa-avatar" x-text="initials(c.name)"></span>
                                <span class="wa-picked__who">
                                    <strong x-text="c.name"></strong>
                                    <small x-text="c.email"></small>
                                </span>
                                <span class="wa-picked__bal" x-text="money(c.balance)"></span>
                            </li>
                        </template>
                        <li class="wa-empty" x-show="results.length === 0">{{ __('No customer matches that search.') }}</li>
                    </ul>
                </div>
                @error('user_id')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- direction --}}
            <div class="form-field">
                <label>{{ __('Type') }}</label>
                <div class="wa-segment" role="radiogroup" aria-label="{{ __('Credit or debit') }}">
                    <label :class="{ 'is-on': direction === 'credit' }">
                        <input type="radio" name="direction" value="credit" x-model="direction">
                        <i class="fa-solid fa-plus"></i> {{ __('Credit') }} <small>{{ __('add money') }}</small>
                    </label>
                    <label :class="{ 'is-on is-debit': direction === 'debit' }">
                        <input type="radio" name="direction" value="debit" x-model="direction">
                        <i class="fa-solid fa-minus"></i> {{ __('Debit') }} <small>{{ __('take money') }}</small>
                    </label>
                </div>
                @error('direction')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- amount + note --}}
            <div class="wa-row">
                <div class="form-field">
                    <label for="wallet_adjust_amount">{{ __('Amount') }} <span class="text-red-500">*</span></label>
                    <div class="wa-amount">
                        <span>$</span>
                        <input type="number" id="wallet_adjust_amount" name="amount" x-ref="amount" x-model="amount"
                            min="0.01" step="0.01" max="100000" class="form-input" placeholder="0.00" required inputmode="decimal">
                    </div>
                    @error('amount')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="wallet_adjust_note">{{ __('Note') }} <span class="text-gray-400" style="font-weight:400">({{ __('optional') }})</span></label>
                    <input type="text" id="wallet_adjust_note" name="note" x-model="note" maxlength="255" class="form-input"
                        placeholder="{{ __('e.g. Refund for order UT-…') }}">
                    @error('note')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- live preview --}}
            <div class="wa-preview" x-show="selected && amountValue > 0" x-cloak :class="{ 'is-bad': overdrawn }">
                <span>{{ __('New balance') }}</span>
                <strong>
                    <span x-text="money(selected ? selected.balance : 0)"></span>
                    <i class="fa-solid fa-arrow-right-long"></i>
                    <span x-text="money(newBalance)"></span>
                </strong>
                <small x-show="overdrawn">{{ __('A debit can’t be more than the current balance.') }}</small>
            </div>

            <div class="form-modal__foot">
                <button type="button" class="modal-cancel" @click="close()">{{ __('Cancel') }}</button>
                <button type="submit" class="form-submit-button" :disabled="!canSubmit">
                    <i class="fa-solid" :class="submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="direction === 'debit' ? '{{ __('Debit wallet') }}' : '{{ __('Credit wallet') }}'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@once
    @push('js')
        <script>
            // "Adjust balance" (no customer) and the row icon (customer preselected).
            window.openWalletAdjust = function (userId) {
                window.dispatchEvent(new CustomEvent('wallet-adjust', { detail: { userId: userId || null } }));
            };

            window.walletAdjustModal = function (config) {
                return {
                    open: false,
                    customers: config.customers || [],
                    selected: null,
                    query: '',
                    cursor: 0,
                    direction: 'credit',
                    amount: '',
                    note: '',
                    submitting: false,

                    init() {
                        if (config.reopen) {
                            this.direction = config.old.direction || 'credit';
                            this.amount = config.old.amount || '';
                            this.note = config.old.note || '';
                            this.selected = this.find(config.old.user_id);
                            this.open = true;
                        }
                    },

                    show(detail = {}) {
                        this.selected = this.find(detail.userId);
                        this.query = '';
                        this.cursor = 0;
                        this.direction = 'credit';
                        this.amount = '';
                        this.note = '';
                        this.submitting = false;
                        this.open = true;
                        this.$nextTick(() => (this.selected ? this.$refs.amount : this.$refs.search)?.focus());
                    },

                    close() { this.open = false; },

                    find(id) {
                        return id ? (this.customers.find(c => String(c.id) === String(id)) || null) : null;
                    },

                    get results() {
                        const q = this.query.trim().toLowerCase();
                        const list = q
                            ? this.customers.filter(c => c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q))
                            : this.customers;
                        return list.slice(0, 8);
                    },

                    move(step) {
                        const n = this.results.length;
                        if (n) this.cursor = (this.cursor + step + n) % n;
                    },

                    pick(c) {
                        if (!c) return;
                        this.selected = c;
                        this.$nextTick(() => this.$refs.amount?.focus());
                    },

                    clearCustomer() {
                        this.selected = null;
                        this.query = '';
                        this.cursor = 0;
                        this.$nextTick(() => this.$refs.search?.focus());
                    },

                    get amountValue() { return Math.round((parseFloat(this.amount) || 0) * 100) / 100; },
                    get newBalance() {
                        const base = this.selected ? this.selected.balance : 0;
                        return this.direction === 'debit' ? base - this.amountValue : base + this.amountValue;
                    },
                    get overdrawn() { return this.direction === 'debit' && this.newBalance < -0.0001; },
                    get canSubmit() { return !!this.selected && this.amountValue > 0 && !this.overdrawn && !this.submitting; },

                    initials(name) { return (name || '?').slice(0, 2).toUpperCase(); },
                    money(v) { return (v < 0 ? '−$' : '$') + Math.abs(v).toFixed(2); },
                };
            };
        </script>
    @endpush
@endonce
