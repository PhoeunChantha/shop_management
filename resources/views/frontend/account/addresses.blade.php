@extends('frontend.account.partials.shell', ['active' => 'addresses'])
@section('title', __('Address Book').' — T-Shirt Shop')

@push('head')
<style>.ut-addr-grid{ display:grid; grid-template-columns:repeat(2,1fr); gap:16px; } @media (max-width:767px){ .ut-addr-grid{ grid-template-columns:1fr; } }</style>
@endpush

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">{{ __('Address book') }}</h2><p class="muted" style="font-size:14px;margin-top:4px">{{ __('Manage your shipping addresses') }}</p></div>
    <button type="button" class="ut-btn ut-btn-ink ut-btn-sm" onclick="openAddrCreate()"><x-frontend.icon n="plus" :size="15" /> {{ __('Add address') }}</button>
</div>

@if (count($addresses))
    <div class="ut-addr-grid">
        @foreach($addresses as $a)
            <div class="ut-card" style="padding:22px">
                <div class="ut-row" style="justify-content:space-between;margin-bottom:12px">
                    <span class="ut-tag ut-tag-soft">{{ $a['label'] }}{{ $a['default'] ? ' · '.__('Default') : '' }}</span>
                    <div class="ut-row" style="gap:6px">
                        <button type="button" class="icon-btn" style="width:32px;height:32px;box-shadow:none;background:var(--bg)"
                            data-id="{{ $a['id'] }}" data-label="{{ $a['label'] }}" data-name="{{ $a['name'] }}"
                            data-phone="{{ $a['phone'] }}" data-street="{{ $a['street'] }}" data-city="{{ $a['city'] }}"
                            data-zip="{{ $a['zip'] }}" data-country="{{ $a['country'] }}" data-default="{{ $a['default'] ? 1 : 0 }}"
                            onclick="openAddrEdit(this)" title="{{ __('Edit') }}"><x-frontend.icon n="user" :size="15" /></button>
                        <form method="POST" action="{{ route('frontend.account.addresses.destroy', $a['id']) }}" onsubmit="return confirm('{{ __('Remove this address?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="icon-btn" style="width:32px;height:32px;box-shadow:none;background:var(--bg);color:var(--text-2)" title="{{ __('Remove') }}"><x-frontend.icon n="trash" :size="15" /></button>
                        </form>
                    </div>
                </div>
                <div style="font-family:var(--font-head);font-weight:700">{{ $a['name'] }}</div>
                <p class="muted" style="font-size:14px;margin:6px 0 0;line-height:1.6">{{ $a['street'] }}@if($a['city'] || $a['zip'])<br>{{ collect([$a['city'], $a['zip']])->filter()->join(', ') }}@endif @if($a['country'])<br>{{ $a['country'] }}@endif @if($a['phone'])<br>{{ $a['phone'] }}@endif</p>
                @unless($a['default'])
                    <form method="POST" action="{{ route('frontend.account.addresses.default', $a['id']) }}" style="margin-top:14px">
                        @csrf @method('PATCH')
                        <button type="submit" class="ut-btn ut-btn-ghost ut-btn-sm">{{ __('Set as default') }}</button>
                    </form>
                @endunless
            </div>
        @endforeach
        <button type="button" class="ut-card" onclick="openAddrCreate()" style="padding:22px;border:1.5px dashed var(--border);background:none;display:grid;place-items:center;color:var(--text-2);font-family:var(--font-head);font-weight:600;min-height:150px;cursor:pointer"><span style="text-align:center"><x-frontend.icon n="plus" :size="26" /><br>{{ __('Add new address') }}</span></button>
    </div>
@else
    <div class="ut-card" style="padding:56px;text-align:center">
        <div style="width:64px;height:64px;border-radius:20px;background:var(--bg);display:grid;place-items:center;margin:0 auto 16px;color:var(--text-3)"><x-frontend.icon n="pin" :size="28" /></div>
        <h3>{{ __('No saved addresses yet') }}</h3><p class="muted" style="margin-top:6px">{{ __('Add an address to check out faster next time.') }}</p>
        <button type="button" class="ut-btn ut-btn-ink" style="margin-top:18px" onclick="openAddrCreate()">{{ __('Add your first address') }}</button>
    </div>
@endif

{{-- add/edit modal (Bootstrap) --}}
<div class="modal fade" id="addrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:var(--r-lg);overflow:hidden">
            <div style="padding:26px">
                <div class="ut-row" style="justify-content:space-between;margin-bottom:18px"><h3 id="addrTitle">{{ __('New address') }}</h3><button type="button" class="icon-btn" style="box-shadow:none;background:var(--bg)" data-bs-dismiss="modal"><x-frontend.icon n="close" :size="18" /></button></div>
                <form id="addrForm" class="ut-col" style="gap:14px" method="POST" action="{{ route('frontend.account.addresses.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="addrMethod" value="POST">
                    <div class="field"><label>{{ __('Label') }}</label><input class="ut-input" name="label" id="af_label" value="{{ old('label') }}" placeholder="{{ __('Home, Work…') }}"></div>
                    <div class="field"><label>{{ __('Full name') }}</label><input class="ut-input" name="name" id="af_name" value="{{ old('name') }}" placeholder="Alex Rivera" required></div>
                    <div class="field"><label>{{ __('Phone') }}</label><input class="ut-input" name="phone" id="af_phone" value="{{ old('phone') }}" placeholder="+855 12 345 678"></div>
                    <div class="field"><label>{{ __('Street address') }}</label><input class="ut-input" name="street" id="af_street" value="{{ old('street') }}" placeholder="123 Market St, Apt 4B" required></div>
                    <div class="ut-form-2" style="display:grid;grid-template-columns:1.5fr 1fr;gap:12px">
                        <div class="field"><label>{{ __('City, State') }}</label><input class="ut-input" name="city" id="af_city" value="{{ old('city') }}" placeholder="San Francisco, CA"></div>
                        <div class="field"><label>{{ __('ZIP') }}</label><input class="ut-input" name="zip" id="af_zip" value="{{ old('zip') }}" placeholder="94103"></div>
                    </div>
                    <div class="field"><label>{{ __('Country') }}</label><input class="ut-input" name="country" id="af_country" value="{{ old('country') }}" placeholder="United States"></div>
                    <label class="ut-row" style="gap:9px;font-size:14px"><input type="checkbox" name="is_default" id="af_default" value="1" style="accent-color:var(--blue);width:16px;height:16px"> {{ __('Set as default address') }}</label>
                    <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">{{ __('Save address') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var addrStoreUrl = "{{ route('frontend.account.addresses.store') }}";
    var addrUpdateBase = "{{ url('account/addresses') }}";
    var addrModalEl = document.getElementById('addrModal');

    // The account page wrapper (.anim-up) has a CSS transform, which would make
    // this position:fixed modal render relative to it instead of the viewport.
    // Re-parent the modal to <body> so Bootstrap centres/overlays it correctly.
    if (addrModalEl && addrModalEl.parentElement !== document.body) {
        document.body.appendChild(addrModalEl);
    }

    function addrModal(){ return bootstrap.Modal.getOrCreateInstance(addrModalEl); }

    function openAddrCreate(){
        var f = document.getElementById('addrForm');
        f.reset();
        f.action = addrStoreUrl;
        document.getElementById('addrMethod').value = 'POST';
        document.getElementById('addrTitle').textContent = '{{ __('New address') }}';
        addrModal().show();
    }

    function openAddrEdit(btn){
        var d = btn.dataset;
        var f = document.getElementById('addrForm');
        f.action = addrUpdateBase + '/' + d.id;
        document.getElementById('addrMethod').value = 'PUT';
        document.getElementById('addrTitle').textContent = '{{ __('Edit address') }}';
        document.getElementById('af_label').value = d.label === 'Address' ? '' : (d.label || '');
        document.getElementById('af_name').value = d.name || '';
        document.getElementById('af_phone').value = d.phone || '';
        document.getElementById('af_street').value = d.street || '';
        document.getElementById('af_city').value = d.city || '';
        document.getElementById('af_zip').value = d.zip || '';
        document.getElementById('af_country').value = d.country || '';
        document.getElementById('af_default').checked = d.default === '1';
        addrModal().show();
    }

    @if ($errors->any())
        // Re-open the form so validation errors aren't lost behind the modal.
        document.addEventListener('DOMContentLoaded', function(){ addrModal().show(); utToast('{{ __('Please check the address form.') }}'); });
    @endif
</script>
@endpush
