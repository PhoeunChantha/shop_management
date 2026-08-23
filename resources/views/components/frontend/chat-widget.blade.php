{{-- Floating live-chat launcher + panel. Signed-in customers get the live thread;
     guests get a launcher that sends them to sign in. Hidden on the full Messages page
     (the page mounts the same thread inline). --}}
@props(['conversation' => null, 'unread' => 0])
@php
    $__chatSettings = app(\App\Services\Admin\SettingService::class);
    $chatCfg = $__chatSettings->chat();
    $siteName = $chatCfg['header_title'] ?: $__chatSettings->siteName();
    $logo = $__chatSettings->logoUrl();
@endphp
@if ($chatCfg['enabled'] && (auth()->check() || $chatCfg['guest_launcher']))
<div class="ut-chat-widget" data-chat-widget>
    @auth
        <section class="ut-chat-panel" data-chat-panel aria-hidden="true" aria-label="{{ __('Live chat') }}" role="dialog">
            <header class="ut-chat-head">
                <span class="ut-chat-head-mark" aria-hidden="true">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="">
                    @else
                        {{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}
                    @endif
                </span>
                <div class="ut-chat-head-copy">
                    <span class="ut-chat-kicker">{{ __('Concierge') }}</span>
                    <strong>{{ $siteName }}</strong>
                    <small><i class="ut-chat-live-dot" aria-hidden="true"></i> {{ $chatCfg['reply_note'] }} · <span class="ut-chat-status-pill" data-chat-status>{{ __('Open') }}</span></small>
                </div>
                <a href="{{ route('frontend.account.messages') }}" class="ut-chat-head-btn ut-hide-mobile" title="{{ __('Open full page') }}" aria-label="{{ __('Open full page') }}">
                    <x-frontend.icon n="arrowR" :size="16" />
                </a>
                <button type="button" class="ut-chat-head-btn" data-chat-close aria-label="{{ __('Close chat') }}">
                    <x-frontend.icon n="close" :size="18" />
                </button>
            </header>
            @include('frontend.partials.chat-thread', ['conversation' => $conversation, 'compact' => true])
        </section>

        <button type="button" class="ut-chat-launcher {{ $unread > 0 ? 'has-unread' : '' }}" data-chat-launcher aria-label="{{ __('Chat with us') }}" aria-haspopup="dialog">
            <span class="ut-chat-launcher-icon ut-chat-launcher-open"><x-frontend.icon n="chat" :size="24" /></span>
            <span class="ut-chat-launcher-icon ut-chat-launcher-close"><x-frontend.icon n="close" :size="22" /></span>
            <span class="ut-chat-launcher-badge" data-chat-count style="{{ $unread > 0 ? '' : 'display:none' }}">{{ $unread }}</span>
            <span class="ut-chat-launcher-label">{{ __('Chat with us') }}</span>
        </button>
    @else
        <a href="{{ route('frontend.login') }}" class="ut-chat-launcher is-guest" data-chat-launcher aria-label="{{ __('Sign in to chat with us') }}">
            <span class="ut-chat-launcher-icon ut-chat-launcher-open"><x-frontend.icon n="chat" :size="24" /></span>
            <span class="ut-chat-launcher-label">{{ __('Sign in to chat') }}</span>
        </a>
        {{-- "Ask about this product" buttons for guests → sign in first (chat.js is auth-only). --}}
        <script>
            document.addEventListener('click', function (e) {
                var b = e.target.closest('[data-chat-product]');
                if (!b) return;
                e.preventDefault();
                if (window.utToast) window.utToast(@json(__('Sign in to chat with us')));
                window.location.href = @json(route('frontend.login'));
            }, true);
        </script>
    @endauth
</div>
@endif
