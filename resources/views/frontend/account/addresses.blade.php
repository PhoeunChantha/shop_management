@extends('frontend.account.partials.shell', ['active' => 'addresses'])
@section('title', 'Address Book — T-Shirt Shop')

@push('head')
<style>.ut-addr-grid{ display:grid; grid-template-columns:repeat(2,1fr); gap:16px; } @media (max-width:767px){ .ut-addr-grid{ grid-template-columns:1fr; } }</style>
@endpush

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">Address book</h2><p class="muted" style="font-size:14px;margin-top:4px">Manage your shipping addresses</p></div>
    <button type="button" class="ut-btn ut-btn-ink ut-btn-sm" data-bs-toggle="modal" data-bs-target="#addrModal"><x-frontend.icon n="plus" :size="15" /> Add address</button>
</div>
<div class="ut-addr-grid">
    @foreach($addresses as $a)
        <div class="ut-card" style="padding:22px">
            <div class="ut-row" style="justify-content:space-between;margin-bottom:12px">
                <span class="ut-tag ut-tag-soft">{{ $a['label'] }}{{ $a['default'] ? ' · Default' : '' }}</span>
                <div class="ut-row" style="gap:6px">
                    <button type="button" class="icon-btn" style="width:32px;height:32px;box-shadow:none;background:var(--bg)" data-bs-toggle="modal" data-bs-target="#addrModal"><x-frontend.icon n="user" :size="15" /></button>
                    <button type="button" class="icon-btn" style="width:32px;height:32px;box-shadow:none;background:var(--bg);color:var(--text-2)" onclick="this.closest('.ut-card').remove(); utToast('Address removed')"><x-frontend.icon n="trash" :size="15" /></button>
                </div>
            </div>
            <div style="font-family:var(--font-head);font-weight:700">{{ $a['name'] }}</div>
            <p class="muted" style="font-size:14px;margin:6px 0 0;line-height:1.6">{{ $a['line'] }}<br>{{ $a['city'] }}<br>{{ $a['country'] }}<br>{{ $a['phone'] }}</p>
            @unless($a['default'])<button type="button" class="ut-btn ut-btn-ghost ut-btn-sm" style="margin-top:14px" onclick="utToast('Set as default')">Set as default</button>@endunless
        </div>
    @endforeach
    <button type="button" class="ut-card" data-bs-toggle="modal" data-bs-target="#addrModal" style="padding:22px;border:1.5px dashed var(--border);background:none;display:grid;place-items:center;color:var(--text-2);font-family:var(--font-head);font-weight:600;min-height:150px;cursor:pointer"><span style="text-align:center"><x-frontend.icon n="plus" :size="26" /><br>Add new address</span></button>
</div>

{{-- add/edit modal (Bootstrap) --}}
<div class="modal fade" id="addrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:var(--r-lg);overflow:hidden">
            <div style="padding:26px">
                <div class="ut-row" style="justify-content:space-between;margin-bottom:18px"><h3>New address</h3><button type="button" class="icon-btn" style="box-shadow:none;background:var(--bg)" data-bs-dismiss="modal"><x-frontend.icon n="close" :size="18" /></button></div>
                <form class="ut-col" style="gap:14px" onsubmit="event.preventDefault(); bootstrap.Modal.getInstance(document.getElementById('addrModal')).hide(); utToast('Address saved');">
                    <div class="field"><label>Label</label><input class="ut-input" placeholder="Home, Work…"></div>
                    <div class="field"><label>Full name</label><input class="ut-input" placeholder="Alex Rivera"></div>
                    <div class="field"><label>Street address</label><input class="ut-input" placeholder="123 Market St, Apt 4B"></div>
                    <div class="ut-form-2" style="display:grid;grid-template-columns:1.5fr 1fr;gap:12px">
                        <div class="field"><label>City, State</label><input class="ut-input" placeholder="San Francisco, CA"></div>
                        <div class="field"><label>ZIP</label><input class="ut-input" placeholder="94103"></div>
                    </div>
                    <label class="ut-row" style="gap:9px;font-size:14px"><input type="checkbox" style="accent-color:var(--blue);width:16px;height:16px"> Set as default address</label>
                    <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Save address</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

