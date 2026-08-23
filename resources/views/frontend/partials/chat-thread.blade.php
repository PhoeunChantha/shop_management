{{-- Shared live-chat thread (DOM contract consumed by public/assets/frontend/js/chat.js).
     Usage: @include('frontend.partials.chat-thread', ['conversation' => $conversation, 'compact' => false]) --}}
@php
    $compact = $compact ?? false;
    $chatCfg = $chatCfg ?? app(\App\Services\Admin\SettingService::class)->chat();
    $isOpen = ($conversation?->status?->value ?? 'open') === 'open';
@endphp
<div class="ut-chat-thread {{ $compact ? 'is-compact' : '' }} {{ $isOpen ? '' : 'is-closed' }}" data-chat-thread>
    <div class="ut-chat-scroll" data-chat-messages-wrap>
        <div class="ut-chat-empty" data-chat-empty style="{{ ($conversation?->messages_count ?? 0) > 0 ? 'display:none' : '' }}">
            <span class="ut-chat-empty-mark"><x-frontend.icon n="chat" :size="24" /></span>
            <h4>{{ $chatCfg['welcome_title'] }}</h4>
            <p>{{ $chatCfg['welcome_text'] }}</p>
        </div>
        <button type="button" class="ut-chat-more" data-chat-more style="display:none">{{ __('Load earlier messages') }}</button>
        <div class="ut-chat-messages" data-chat-messages aria-live="polite" aria-relevant="additions"></div>
        <div class="ut-chat-typing" data-chat-typing aria-live="polite">
            <span class="ut-chat-typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
            <span><strong data-chat-typing-name>{{ __('Support') }}</strong> {{ __('is typing…') }}</span>
        </div>
    </div>

    {{-- attached product chip ("Ask about this product"), filled by chat.js --}}
    <div class="ut-chat-attach" data-chat-attach hidden>
        <span class="ut-chat-attach-kicker">{{ __('Asking about') }}</span>
        <a class="ut-chat-attach-card" data-chat-attach-link href="#" target="_blank" rel="noopener">
            <img data-chat-attach-img alt="" hidden>
            <span class="ut-chat-attach-ph" data-chat-attach-ph><x-frontend.icon n="bag" :size="16" /></span>
            <span class="ut-chat-attach-copy">
                <strong data-chat-attach-name></strong>
                <small data-chat-attach-price></small>
            </span>
        </a>
        <button type="button" class="ut-chat-attach-remove" data-chat-attach-remove aria-label="{{ __('Remove product') }}"><x-frontend.icon n="close" :size="14" /></button>
    </div>

    <form class="ut-chat-composer" data-chat-form autocomplete="off">
        <label class="visually-hidden" for="chatInput{{ $compact ? 'W' : 'P' }}">{{ __('Your message') }}</label>
        <textarea id="chatInput{{ $compact ? 'W' : 'P' }}" class="ut-chat-input" data-chat-input rows="1" maxlength="2000"
            placeholder="{{ __('Write a message…') }}"></textarea>
        <button type="submit" class="ut-chat-send" data-chat-send aria-label="{{ __('Send') }}">
            <x-frontend.icon n="send" :size="18" />
        </button>
    </form>
    <p class="ut-chat-closed-note">{{ __('This conversation was closed by our team. Send a message to reopen it.') }}</p>
</div>
