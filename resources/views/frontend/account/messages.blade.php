@extends('frontend.account.partials.shell', ['active' => 'messages'])
@section('title', __('Messages').' — '.app(\App\Services\Admin\SettingService::class)->siteName())

@section('account')
@php
    $assignee = $conversation->assignee;
    $isOpen = $conversation->isOpen();
@endphp
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div>
        <span class="ut-chat-kicker" style="display:block;margin-bottom:4px">{{ __('Concierge') }}</span>
        <h2 style="font-size:24px">{{ __('Messages') }}</h2>
        <p class="muted" style="font-size:14px;margin-top:4px">
            @if ($assignee)
                {{ __('You are talking with') }} <strong style="color:var(--ink)">{{ $assignee->name }}</strong>
            @else
                {{ __('Chat with our team about orders, sizing, or returns.') }}
            @endif
        </p>
    </div>
    <span class="ut-tag {{ $isOpen ? 'ut-tag-soft' : '' }} ut-chat-status-pill {{ $isOpen ? '' : 'is-closed' }}" data-chat-status style="{{ $isOpen ? 'background:#e9efe6;color:var(--success)' : '' }}">{{ $isOpen ? __('Open') : __('Closed') }}</span>
</div>

<div class="ut-card ut-chat-page-card">
    @include('frontend.partials.chat-thread', ['conversation' => $conversation, 'compact' => false])
</div>
@endsection
