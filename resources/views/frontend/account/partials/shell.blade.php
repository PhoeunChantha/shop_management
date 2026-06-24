{{-- Account page shell: profile header + sidebar grid.
     Usage:
       @extends('frontend.account.partials.shell', ['active' => 'orders'])
       @section('account') ...page body... @endsection --}}
@extends('frontend.layouts.frontend')
@php $u = \App\Support\Catalog::user(); @endphp

@push('head')
<style>
    .ut-account-grid { display:grid; grid-template-columns:232px 1fr; gap:30px; align-items:start; }
    @media (max-width:1024px){ .ut-account-grid{ grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="ut-wrap anim-up" style="padding-top:30px">
    {{-- profile header --}}
    <div class="ut-card" style="padding:24px;margin-bottom:26px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
        <span style="width:64px;height:64px;border-radius:50%;background:var(--ink);color:#fff;display:grid;place-items:center;font-family:var(--font-head);font-weight:700;font-size:24px;flex-shrink:0">{{ $u['first'][0] }}</span>
        <div style="flex:1;min-width:160px">
            <h1 style="font-size:26px">Hi, {{ $u['first'] }}</h1>
            <p class="muted" style="font-size:14px">{{ $u['email'] }}</p>
        </div>
        <div class="ut-row" style="gap:10px">
            <span class="ut-tag" style="background:#fde9d9;color:var(--orange-hover)"><x-frontend.icon n="spark" :size="13" style="vertical-align:-2px" /> {{ $u['tier'] }} member</span>
            <span class="ut-tag ut-tag-soft">{{ number_format($u['points']) }} points</span>
        </div>
    </div>

    <div class="ut-account-grid">
        <x-frontend.account-sidebar :active="$active ?? 'dashboard'" />
        <div>@yield('account')</div>
    </div>
</div>
@endsection

