@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $c = $currency ?? null;
@endphp

{{-- Live preview of 10 base units in this currency, so the rate is easy to sanity-check. --}}
<form action="{{ $action }}" method="POST"
    x-data="{
        symbol: @js(old('symbol', $c->symbol ?? '')),
        position: @js(old('symbol_position', $c->symbol_position ?? 'before')),
        rate: @js((string) old('exchange_rate', $c ? (float) $c->exchange_rate : '')),
        decimals: @js((string) old('decimals', $c->decimals ?? 2)),
        get preview() {
            const r = parseFloat(this.rate);
            if (!(r > 0)) return '—';
            const d = parseInt(this.decimals, 10) || 0;
            const n = (10 * r).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
            return this.position === 'after' ? n + this.symbol : this.symbol + n;
        }
    }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2 md:col-span-1">
            <label for="code">{{ __('Code') }} <span class="text-red-500">*</span></label>
            <input value="{{ old('code', $c->code ?? '') }}" type="text" name="code" id="code" maxlength="3"
                class="form-input" placeholder="KHR" style="text-transform:uppercase" required>
            <p class="form-help">{{ __('3-letter ISO code, e.g. KHR, EUR, THB.') }}</p>
            @error('code')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">{{ __('Name') }} <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $c->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="{{ __('e.g. Cambodian Riel') }}" required>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="symbol">{{ __('Symbol') }} <span class="text-red-500">*</span></label>
            <input x-model="symbol" value="{{ old('symbol', $c->symbol ?? '') }}" type="text" name="symbol" id="symbol" maxlength="8"
                class="form-input" placeholder="៛" required>
            @error('symbol')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="symbol_position">{{ __('Symbol position') }}</label>
            <select x-model="position" name="symbol_position" id="symbol_position" class="form-input">
                <option value="before" @selected(old('symbol_position', $c->symbol_position ?? 'before') === 'before')>{{ __('Before amount') }} (€10)</option>
                <option value="after" @selected(old('symbol_position', $c->symbol_position ?? 'before') === 'after')>{{ __('After amount') }} (10៛)</option>
            </select>
            @error('symbol_position')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="exchange_rate">{{ __('Exchange rate') }} <span class="text-red-500">*</span></label>
            <input x-model="rate" value="{{ old('exchange_rate', $c ? (float) $c->exchange_rate : '') }}" type="number" step="any" min="0"
                name="exchange_rate" id="exchange_rate" class="form-input" placeholder="4100" required>
            <p class="form-help">{{ __('How many of this currency equal 1 :base.', ['base' => $base['code']]) }}</p>
            @error('exchange_rate')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="decimals">{{ __('Decimals') }}</label>
            <select x-model="decimals" name="decimals" id="decimals" class="form-input">
                @foreach ([0 => '0 (4,100)', 2 => '2 (4,100.00)', 3 => '3 (4,100.000)'] as $value => $label)
                    <option value="{{ $value }}" @selected((int) old('decimals', $c->decimals ?? 2) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('decimals')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">{{ __('Sort order') }}</label>
            <input value="{{ old('sort_order', $c->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">{{ __('Status') }}</label>
            <select name="status" id="status" class="form-input">
                <option value="1" @selected((int) old('status', $c->status ?? 1) === 1)>{{ __('Enabled') }}</option>
                <option value="0" @selected((int) old('status', $c->status ?? 1) === 0)>{{ __('Disabled') }}</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="col-span-2 price-preview">
            <span>{{ money(10) }} {{ __('shows as') }}</span>
            <strong x-text="preview"></strong>
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.currencies.index') }}" class="form-cancel-button">{{ __('Cancel') }}</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
