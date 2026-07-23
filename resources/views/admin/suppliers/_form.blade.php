@php($s = $supplier ?? null)

<div class="restock-form-grid">
    <section class="premium-card restock-form-card">
        <div class="form-section__header">
            <span class="form-section__icon"><i class="fa-solid fa-truck-field"></i></span>
            <div>
                <p class="section-kicker">Supplier profile</p>
                <h3>{{ $s ? 'Edit supplier' : 'New supplier' }}</h3>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label for="name">Supplier name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" class="form-input" required value="{{ old('name', $s->name ?? '') }}">
                @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="contact_name">Contact person</label>
                <input type="text" name="contact_name" id="contact_name" class="form-input" value="{{ old('contact_name', $s->contact_name ?? '') }}">
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-input" value="{{ old('email', $s->email ?? '') }}">
            </div>
            <div class="form-field">
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone" class="form-input" value="{{ old('phone', $s->phone ?? '') }}">
            </div>
            <div class="form-field form-field--wide">
                <label for="address">Address</label>
                <textarea name="address" id="address" class="form-input" rows="3">{{ old('address', $s->address ?? '') }}</textarea>
            </div>
            <label class="restock-toggle">
                <input type="checkbox" name="status" value="1" @checked(old('status', $s->status ?? true))>
                <span><i class="fa-solid fa-circle-check"></i></span>
                <strong>Active supplier</strong>
            </label>
        </div>
    </section>
</div>

<div class="form-actions-sticky">
    <a href="{{ route('admin.suppliers.index') }}" class="ghost-button ghost-button--panel">
        <i class="fa-solid fa-arrow-left"></i><span>Cancel</span>
    </a>
    <button type="submit" class="premium-button premium-button--dark">
        <i class="fa-solid fa-floppy-disk"></i><span>Save supplier</span>
    </button>
</div>
