@php
    use App\Enums\CouponType;
    $isEdit = ($mode ?? 'create') === 'edit';
    $typeValue = old('type', isset($coupon) ? $coupon->type->value : CouponType::Percentage->value);
    $fmt = fn ($date) => $date?->format('Y-m-d\TH:i');
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Code --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="code">Coupon Code <span class="text-red-500">*</span></label>
            <input value="{{ old('code', $coupon->code ?? '') }}" type="text" name="code" id="code"
                class="form-input font-mono" placeholder="e.g. SUMMER25" style="text-transform: uppercase;" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Letters, numbers, dashes and underscores only.</small>
            @error('code')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $coupon->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $coupon->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Type --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="type">Discount Type <span class="text-red-500">*</span></label>
            <select name="type" id="type" class="form-input">
                @foreach (CouponType::options() as $value => $label)
                    <option value="{{ $value }}" {{ $typeValue === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Value --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="value">Value <span class="text-red-500">*</span></label>
            <input value="{{ old('value', $coupon->value ?? '') }}" type="number" step="0.01" min="0" name="value" id="value"
                class="form-input" placeholder="e.g. 25" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Percent (0–100) for percentage type, or a fixed amount.</small>
            @error('value')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Min spend --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="min_spend">Minimum Spend</label>
            <input value="{{ old('min_spend', $coupon->min_spend ?? '') }}" type="number" step="0.01" min="0" name="min_spend" id="min_spend"
                class="form-input" placeholder="Optional — e.g. 100">
            @error('min_spend')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Max discount (cap) --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="max_discount">Max Discount Cap</label>
            <input value="{{ old('max_discount', $coupon->max_discount ?? '') }}" type="number" step="0.01" min="0" name="max_discount" id="max_discount"
                class="form-input" placeholder="Optional — caps a percentage discount">
            @error('max_discount')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Usage limit --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="usage_limit">Usage Limit</label>
            <input value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" type="number" min="1" name="usage_limit" id="usage_limit"
                class="form-input" placeholder="Optional — leave blank for unlimited">
            @error('usage_limit')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Dates --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="starts_at">Starts At</label>
            <input value="{{ old('starts_at', $fmt($coupon->starts_at ?? null)) }}" type="datetime-local" name="starts_at" id="starts_at"
                class="form-input">
            @error('starts_at')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="expires_at">Expires At</label>
            <input value="{{ old('expires_at', $fmt($coupon->expires_at ?? null)) }}" type="datetime-local" name="expires_at" id="expires_at"
                class="form-input">
            @error('expires_at')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.coupons.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
