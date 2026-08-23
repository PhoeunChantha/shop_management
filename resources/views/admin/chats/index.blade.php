<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Live Chat') }}</h2>
        </div>
    </x-slot>

    @php
        $me = auth()->user();
        $activeStatus = $filters['status'] ?? null;
        $activeAssigned = $filters['assigned'] ?? null;
        $activeUnread = ! empty($filters['unread']);
        $chipUrl = fn (array $override) => route('admin.chats.index', array_filter(array_merge(
            request()->except(['page', 'conversation']),
            $override,
            ['conversation' => $selected['id'] ?? null],
        ), fn ($v) => $v !== null && $v !== ''));
        $chatConfig = [
            'selectedId' => $selected['id'] ?? null,
            'me' => ['id' => $me->id, 'name' => $me->name],
            'urls' => [
                'index' => route('admin.chats.index'),
                'show' => route('admin.chats.show', ['conversation' => '__ID__']),
                'messages' => route('admin.chats.messages', ['conversation' => '__ID__']),
                'store' => route('admin.chats.store', ['conversation' => '__ID__']),
                'read' => route('admin.chats.read', ['conversation' => '__ID__']),
                'status' => route('admin.chats.status', ['conversation' => '__ID__']),
                'assign' => route('admin.chats.assign', ['conversation' => '__ID__']),
                'unread' => route('admin.chats.unread'),
                'customer' => route('admin.customers.show', ['email' => '__EMAIL__']),
            ],
            'i18n' => [
                'today' => __('Today'), 'yesterday' => __('Yesterday'), 'you' => __('You'),
                'customer' => __('Customer'), 'typing' => __('is typing…'), 'seen' => __('Seen'),
                'sent' => __('Sent'), 'failed' => __('Could not send — try again.'),
                'closed' => __('Conversation closed.'), 'reopened' => __('Conversation reopened.'),
                'assigned' => __('Assignment updated.'), 'unassigned' => __('Unassigned'),
            ],
        ];
    @endphp

    <div class="admin-page admin-chat-page" x-data="adminChatInbox(@js($chatConfig))" x-init="init()">
        <div class="admin-chat-shell" :class="{ 'has-thread': conv }">

            {{-- ============ LEFT: inbox list (server-rendered, AJAX-swappable) ============ --}}
            <aside class="admin-chat-list premium-card">
                <div data-ajax-page data-chat-list>
                    <div class="admin-chat-list__head">
                        <div class="admin-chat-list__title">
                            <div>
                                <p class="section-kicker mb-0">{{ __('Inbox') }}</p>
                                <h3 class="mb-0">{{ __('Conversations') }}</h3>
                            </div>
                            <div class="admin-chat-list__tools">
                                <button type="button" class="admin-chat-iconbtn" :class="{ 'is-muted': muted }" @click="toggleMute()"
                                    :title="muted ? '{{ __('Unmute alert sound') }}' : '{{ __('Mute alert sound') }}'" :aria-label="muted ? '{{ __('Unmute alert sound') }}' : '{{ __('Mute alert sound') }}'">
                                    <i class="fa-solid" :class="muted ? 'fa-bell-slash' : 'fa-bell'"></i>
                                </button>
                                @can('edit settings')
                                    <a href="{{ route('admin.settings.index', ['tab' => 'chat']) }}" class="admin-chat-iconbtn" title="{{ __('Chat settings') }}" aria-label="{{ __('Chat settings') }}"><i class="fa-solid fa-sliders"></i></a>
                                @endcan
                            </div>
                        </div>
                        <x-search-input :action="route('admin.chats.index')" :placeholder="__('Search customer or message…')" />
                    </div>

                    <div class="admin-chat-chips">
                        <a href="{{ $chipUrl(['status' => null, 'unread' => null, 'assigned' => null]) }}" data-ajax-link
                            class="admin-chat-chip {{ ! $activeStatus && ! $activeUnread && ! $activeAssigned ? 'is-active' : '' }}">{{ __('All') }}</a>
                        <a href="{{ $chipUrl(['status' => 'open', 'unread' => null, 'assigned' => null]) }}" data-ajax-link
                            class="admin-chat-chip {{ $activeStatus === 'open' && ! $activeUnread && ! $activeAssigned ? 'is-active' : '' }}">{{ __('Open') }} <b>{{ $stats['open'] }}</b></a>
                        <a href="{{ $chipUrl(['unread' => 1, 'status' => null, 'assigned' => null]) }}" data-ajax-link
                            class="admin-chat-chip {{ $activeUnread ? 'is-active' : '' }}">{{ __('Unread') }} <b data-chat-stat-unread>{{ $stats['unread'] }}</b></a>
                        <a href="{{ $chipUrl(['assigned' => 'me', 'status' => null, 'unread' => null]) }}" data-ajax-link
                            class="admin-chat-chip {{ $activeAssigned === 'me' ? 'is-active' : '' }}">{{ __('Mine') }} <b>{{ $stats['mine'] }}</b></a>
                        <a href="{{ $chipUrl(['status' => 'closed', 'unread' => null, 'assigned' => null]) }}" data-ajax-link
                            class="admin-chat-chip {{ $activeStatus === 'closed' ? 'is-active' : '' }}">{{ __('Closed') }} <b>{{ $stats['closed'] }}</b></a>
                    </div>

                    <ul class="admin-chat-rows" data-chat-rows>
                        @forelse ($conversations as $c)
                            @php
                                $customer = $c->customer;
                                $initials = collect(explode(' ', trim((string) ($customer?->name ?? '?'))))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?';
                            @endphp
                            <li>
                                <button type="button" class="admin-chat-row {{ ($selected['id'] ?? null) === $c->id ? 'is-active' : '' }} {{ $c->admin_unread > 0 ? 'is-unread' : '' }} {{ $c->isOpen() ? '' : 'is-closed' }}"
                                    data-conv-row="{{ $c->id }}" @click="open({{ $c->id }})">
                                    <span class="admin-chat-avatar">
                                        @if ($customer?->avatarUrl())
                                            <img src="{{ $customer->avatarUrl() }}" alt="">
                                        @else
                                            {{ $initials }}
                                        @endif
                                    </span>
                                    <span class="admin-chat-row__body">
                                        <span class="admin-chat-row__top">
                                            <strong data-row-name>{{ $customer?->name ?? __('Deleted customer') }}</strong>
                                            <time data-row-time datetime="{{ $c->last_message_at?->toIso8601String() }}">{{ $c->last_message_at?->diffForHumans(short: true) }}</time>
                                        </span>
                                        <span class="admin-chat-row__bottom">
                                            <span class="admin-chat-row__preview" data-row-preview>{{ $c->last_message_preview ?: __('No messages yet') }}</span>
                                            <span class="admin-chat-row__unread" data-row-unread style="{{ $c->admin_unread > 0 ? '' : 'display:none' }}">{{ $c->admin_unread }}</span>
                                        </span>
                                        <span class="admin-chat-row__meta">
                                            <span class="admin-chat-dot {{ $c->isOpen() ? 'is-open' : 'is-closed' }}" data-row-status></span>
                                            <span data-row-assignee>{{ $c->assignee?->name ?? __('Unassigned') }}</span>
                                        </span>
                                    </span>
                                </button>
                            </li>
                        @empty
                            <li class="admin-chat-rows__empty">
                                <i class="fa-regular fa-comments"></i>
                                <p>{{ __('No conversations match these filters.') }}</p>
                            </li>
                        @endforelse
                    </ul>

                    @if ($conversations->hasPages())
                        <x-table-footer :paginator="$conversations" :label="__('conversations')" class="admin-chat-list__footer" />
                    @endif
                </div>
            </aside>

            {{-- ============ RIGHT: thread pane (Alpine) ============ --}}
            <section class="admin-chat-thread premium-card" x-ref="thread">
                {{-- empty state --}}
                <div class="admin-chat-thread__empty" x-show="!conv && !loadingConv" x-cloak>
                    <span class="admin-chat-thread__empty-mark"><i class="fa-regular fa-comment-dots"></i></span>
                    <h3>{{ __('Pick a conversation') }}</h3>
                    <p>{{ __('New customer messages appear here in real time. Select a thread on the left to reply.') }}</p>
                </div>

                <div class="admin-chat-thread__loading" x-show="loadingConv" x-cloak>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                </div>

                <template x-if="conv">
                    <div class="admin-chat-thread__inner">
                        {{-- header --}}
                        <header class="admin-chat-thread__head">
                            <button type="button" class="admin-chat-back" @click="closeThread()" aria-label="{{ __('Back') }}"><i class="fa-solid fa-arrow-left"></i></button>
                            <span class="admin-chat-avatar is-lg">
                                <template x-if="conv.customer && conv.customer.avatar"><img :src="conv.customer.avatar" alt=""></template>
                                <template x-if="!(conv.customer && conv.customer.avatar)"><span x-text="initials(conv.customer ? conv.customer.name : '?')"></span></template>
                            </span>
                            <div class="admin-chat-thread__who">
                                <strong x-text="conv.customer ? conv.customer.name : '{{ __('Deleted customer') }}'"></strong>
                                <small>
                                    <span x-text="conv.customer ? conv.customer.email : ''"></span>
                                    <template x-if="conv.customer">
                                        <a :href="urls.customer.replace('__EMAIL__', encodeURIComponent(conv.customer.email))" target="_blank" rel="noopener" class="admin-chat-link">{{ __('View profile') }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    </template>
                                </small>
                            </div>
                            <div class="admin-chat-thread__tools">
                                <span class="admin-chat-status" :class="conv.status === 'open' ? 'is-open' : 'is-closed'">
                                    <i></i><span x-text="conv.status === 'open' ? '{{ __('Open') }}' : '{{ __('Closed') }}'"></span>
                                </span>
                                <label class="admin-chat-assign">
                                    <i class="fa-regular fa-user"></i>
                                    <select :value="conv.assignee ? conv.assignee.id : ''" @change="assign($event.target.value)" :disabled="busy">
                                        <option value="">{{ __('Unassigned') }}</option>
                                        @foreach ($staff as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}{{ $s->id === $me->id ? ' ('.__('me').')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="button" class="admin-chat-btn" :disabled="busy" @click="toggleStatus()">
                                    <i class="fa-regular" :class="conv.status === 'open' ? 'fa-circle-check' : 'fa-folder-open'"></i>
                                    <span x-text="conv.status === 'open' ? '{{ __('Close') }}' : '{{ __('Reopen') }}'"></span>
                                </button>
                            </div>
                        </header>

                        {{-- messages --}}
                        <div class="admin-chat-scroll" x-ref="scroll" @scroll.passive="onScroll()">
                            <button type="button" class="admin-chat-more" x-show="hasMore" :disabled="loadingMore" @click="loadEarlier()">
                                <span x-show="!loadingMore">{{ __('Load earlier messages') }}</span>
                                <span x-show="loadingMore" class="spinner-border spinner-border-sm"></span>
                            </button>

                            <div class="admin-chat-empty-thread" x-show="!messages.length && !loadingConv">
                                <p>{{ __('No messages yet — say hello.') }}</p>
                            </div>

                            <template x-for="item in timeline" :key="item.key">
                                <div>
                                    <template x-if="item.type === 'day'">
                                        <div class="admin-chat-day"><span x-text="item.label"></span></div>
                                    </template>
                                    <template x-if="item.type === 'msg'">
                                        <div class="admin-chat-msg" :class="{ 'is-staff': item.m.sender_role === 'staff', 'is-customer': item.m.sender_role === 'customer', 'is-grouped': item.grouped }">
                                            <span class="admin-chat-avatar is-sm" x-show="item.m.sender_role === 'customer'">
                                                <template x-if="item.m.sender.avatar"><img :src="item.m.sender.avatar" alt=""></template>
                                                <template x-if="!item.m.sender.avatar"><span x-text="initials(item.m.sender.name)"></span></template>
                                            </span>
                                            <div class="admin-chat-msg__body">
                                                <span class="admin-chat-msg__name" x-show="!item.grouped" x-text="item.m.sender_role === 'staff' ? (item.m.sender.id === me.id ? '{{ __('You') }}' : item.m.sender.name) : item.m.sender.name"></span>
                                                <template x-if="item.m.product">
                                                    <div class="admin-chat-product">
                                                        <template x-if="item.m.product.image"><img :src="item.m.product.image" alt=""></template>
                                                        <template x-if="!item.m.product.image"><span class="admin-chat-product__ph"><i class="fa-regular fa-image"></i></span></template>
                                                        <span class="admin-chat-product__copy">
                                                            <span class="admin-chat-product__kicker">{{ __('Asking about') }}</span>
                                                            <strong x-text="item.m.product.name"></strong>
                                                            <small x-text="item.m.product.price_label"></small>
                                                            <span class="admin-chat-product__links">
                                                                <a :href="item.m.product.url" target="_blank" rel="noopener">{{ __('View in store') }}</a>
                                                                <a :href="item.m.product.admin_url" target="_blank" rel="noopener">{{ __('Edit product') }}</a>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </template>
                                                <div class="admin-chat-bubble" x-html="format(item.m.body)"></div>
                                                <span class="admin-chat-msg__meta">
                                                    <span x-text="time(item.m.created_at)"></span>
                                                    <template x-if="item.m.sender_role === 'staff' && item.last"><em x-text="item.m.read_at ? i18n.seen : i18n.sent"></em></template>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="admin-chat-typing" x-show="typingName" x-cloak>
                                <span class="admin-chat-typing__dots"><i></i><i></i><i></i></span>
                                <span><strong x-text="typingName"></strong> {{ __('is typing…') }}</span>
                            </div>
                        </div>

                        {{-- composer --}}
                        <form class="admin-chat-composer" @submit.prevent="send()">
                            <textarea x-ref="input" x-model="draft" rows="1" maxlength="2000" class="admin-chat-input"
                                placeholder="{{ __('Write a reply… (Enter to send, Shift+Enter for a new line)') }}"
                                @input="autosize(); whisper()"
                                @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); send(); }"></textarea>
                            <button type="submit" class="admin-chat-send" :disabled="sending || !draft.trim()" aria-label="{{ __('Send') }}">
                                <i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                            </button>
                        </form>
                        <p class="admin-chat-closed-note" x-show="conv.status !== 'open'">{{ __('This thread is closed. Replying will reopen it.') }}</p>
                    </div>
                </template>
            </section>
        </div>
    </div>

    @once
        @push('js')
            <script>
                window.adminChatInbox = function (cfg) {
                    const CSRF = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
                    const http = async (url, method = 'GET', data = null) => {
                        const opts = { method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF } };
                        if (data) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(data); }
                        const r = await fetch(url, opts);
                        const json = await r.json().catch(() => ({}));
                        if (!r.ok) { const e = new Error(json.message || r.statusText); e.data = json; throw e; }
                        return json;
                    };
                    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                    const dayKey = (d) => d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();

                    return {
                        urls: cfg.urls, i18n: cfg.i18n, me: cfg.me,
                        conv: null, messages: [], ids: new Set(), hasMore: false,
                        loadingConv: false, loadingMore: false, sending: false, busy: false,
                        draft: '', typingName: '', typingTimer: null, lastWhisper: 0,
                        channel: null, pollTimer: null, feedOff: [], feedAttached: false,
                        muted: !!(window.adminChatFeed && window.adminChatFeed.isMuted && window.adminChatFeed.isMuted()),

                        toggleMute() {
                            const feed = window.adminChatFeed; if (!feed || !feed.setMuted) return;
                            this.muted = !this.muted;
                            feed.setMuted(this.muted);
                            if (!this.muted) feed.play(); // preview the sound when turning it back on
                            window.toastr && window.toastr.info(this.muted ? '{{ __('Alert sound muted') }}' : '{{ __('Alert sound on') }}');
                        },

                        init() {
                            // The websocket feed loads lazily (resources/js/echo.js); attach now if it
                            // already exists, otherwise when it announces itself. Poll until it is live.
                            this.startPolling();
                            if (window.adminChatFeed) this.attachFeed(window.adminChatFeed);
                            else document.addEventListener('admin-chat:ready', (e) => this.attachFeed(e.detail.feed), { once: true });
                            document.addEventListener('ajax:page-loaded', () => this.syncActiveRow());
                            window.addEventListener('beforeunload', () => this.teardown());
                            if (cfg.selectedId) this.open(cfg.selectedId, { replace: true });
                        },
                        attachFeed(feed) {
                            if (!feed || this.feedAttached) return;
                            this.feedAttached = true;
                            feed.inboxActive = true;
                            this.muted = !!(feed.isMuted && feed.isMuted());
                            this.feedOff.push(feed.on('message', (e) => this.onFeedMessage(e)));
                            this.feedOff.push(feed.on('conversation', (e) => this.onFeedConversation(e)));
                            if (feed.live) this.stopPolling();
                        },
                        startPolling() { if (!this.pollTimer) this.pollTimer = setInterval(() => this.poll(), 8000); },
                        stopPolling() { if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; } },
                        teardown() {
                            this.feedOff.forEach((off) => off());
                            this.stopPolling();
                            this.leaveChannel();
                            if (window.adminChatFeed) window.adminChatFeed.inboxActive = false;
                        },
                        url(name, id) { return this.urls[name].replace('__ID__', id); },

                        /* ---- open / close ---- */
                        async open(id, opts = {}) {
                            if (this.conv && this.conv.id === id) return;
                            this.loadingConv = true; this.conv = null; this.messages = []; this.ids = new Set(); this.typingName = '';
                            this.leaveChannel();
                            try {
                                const res = await http(this.url('show', id));
                                this.conv = res.conversation;
                                this.hasMore = !!res.has_more;
                                (res.messages || []).forEach((m) => this.push(m));
                                this.joinChannel(id);
                                this.syncActiveRow();
                                const u = new URL(window.location.href); u.searchParams.set('conversation', id);
                                history[opts.replace ? 'replaceState' : 'pushState']({}, '', u.toString());
                                this.$nextTick(() => { this.scrollToBottom(true); if (window.matchMedia('(min-width: 992px)').matches) this.$refs.input && this.$refs.input.focus(); });
                                this.markRead();
                            } catch (e) {
                                window.toastr && window.toastr.error(e.message || 'Error');
                            } finally { this.loadingConv = false; }
                        },
                        closeThread() {
                            this.leaveChannel(); this.conv = null; this.messages = []; this.ids = new Set();
                            const u = new URL(window.location.href); u.searchParams.delete('conversation'); history.replaceState({}, '', u.toString());
                            this.syncActiveRow();
                        },
                        syncActiveRow() {
                            document.querySelectorAll('[data-conv-row]').forEach((row) => {
                                row.classList.toggle('is-active', !!this.conv && Number(row.dataset.convRow) === this.conv.id);
                            });
                        },

                        /* ---- messages ---- */
                        push(m) {
                            if (!m || this.ids.has(m.id)) return false;
                            this.ids.add(m.id); this.messages.push(m); this.messages.sort((a, b) => a.id - b.id); return true;
                        },
                        get timeline() {
                            const out = []; let lastDay = null, lastSender = null, lastTime = 0;
                            const lastStaff = [...this.messages].reverse().find((m) => m.sender_role === 'staff');
                            this.messages.forEach((m) => {
                                const d = new Date(m.created_at); const dk = dayKey(d);
                                if (dk !== lastDay) { out.push({ type: 'day', key: 'd' + dk, label: this.dayLabel(d) }); lastDay = dk; lastSender = null; }
                                const grouped = lastSender === m.sender.id && (d - lastTime) < 5 * 60 * 1000;
                                lastSender = m.sender.id; lastTime = d;
                                out.push({ type: 'msg', key: 'm' + m.id, m, grouped, last: !!lastStaff && lastStaff.id === m.id });
                            });
                            return out;
                        },
                        dayLabel(d) {
                            const now = new Date(), y = new Date(); y.setDate(now.getDate() - 1);
                            if (dayKey(d) === dayKey(now)) return this.i18n.today;
                            if (dayKey(d) === dayKey(y)) return this.i18n.yesterday;
                            return d.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
                        },
                        time(iso) { return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }); },
                        initials(name) { return String(name || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0] || '').join('').toUpperCase() || '?'; },
                        format(body) {
                            return esc(body).replace(/(https?:\/\/[^\s<]+)/g, (u) => '<a href="' + u + '" target="_blank" rel="noopener nofollow">' + u + '</a>').replace(/\n/g, '<br>');
                        },
                        scrollToBottom(force) {
                            const el = this.$refs.scroll; if (!el) return;
                            const near = el.scrollHeight - el.scrollTop - el.clientHeight < 120;
                            if (force || near) this.$nextTick(() => { el.scrollTop = el.scrollHeight; });
                        },
                        onScroll() { const el = this.$refs.scroll; if (el && el.scrollTop < 40 && this.hasMore && !this.loadingMore) this.loadEarlier(); },
                        async loadEarlier() {
                            if (!this.conv || !this.hasMore || this.loadingMore || !this.messages.length) return;
                            this.loadingMore = true;
                            const el = this.$refs.scroll; const prev = el ? el.scrollHeight : 0;
                            try {
                                const res = await http(this.url('messages', this.conv.id) + '?before_id=' + this.messages[0].id);
                                (res.messages || []).forEach((m) => this.push(m));
                                this.hasMore = !!res.has_more;
                                this.$nextTick(() => { if (el) el.scrollTop = el.scrollHeight - prev; });
                            } catch (e) { /* ignore */ } finally { this.loadingMore = false; }
                        },
                        async send() {
                            const body = this.draft.trim();
                            if (!body || !this.conv || this.sending) return;
                            this.sending = true;
                            try {
                                const res = await http(this.url('store', this.conv.id), 'POST', { body });
                                this.draft = ''; this.push(res.message); this.applyConversation(res.conversation);
                                this.$nextTick(() => { this.autosize(); this.scrollToBottom(true); });
                            } catch (e) {
                                const msg = (e.data && e.data.errors && e.data.errors.body && e.data.errors.body[0]) || this.i18n.failed;
                                window.toastr && window.toastr.error(msg);
                            } finally { this.sending = false; this.$nextTick(() => this.$refs.input && this.$refs.input.focus()); }
                        },
                        autosize() { const el = this.$refs.input; if (!el) return; el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 160) + 'px'; },
                        async markRead() {
                            if (!this.conv) return;
                            const hadUnread = this.conv.admin_unread > 0 || this.messages.some((m) => m.sender_role === 'customer' && !m.read_at);
                            if (!hadUnread) return;
                            this.conv.admin_unread = 0; this.updateRow(this.conv);
                            try {
                                const res = await http(this.url('read', this.conv.id), 'POST', {});
                                if (window.adminChatFeed) window.adminChatFeed.setUnread(res.unread_total);
                                this.setStat(res.unread_total);
                            } catch (e) { /* ignore */ }
                        },

                        /* ---- status / assign ---- */
                        async toggleStatus() {
                            if (!this.conv || this.busy) return;
                            this.busy = true;
                            const next = this.conv.status === 'open' ? 'closed' : 'open';
                            try {
                                const res = await http(this.url('status', this.conv.id), 'PATCH', { status: next });
                                this.applyConversation(res.conversation);
                                window.toastr && window.toastr.success(next === 'closed' ? this.i18n.closed : this.i18n.reopened);
                            } catch (e) { window.toastr && window.toastr.error(e.message); } finally { this.busy = false; }
                        },
                        async assign(userId) {
                            if (!this.conv || this.busy) return;
                            this.busy = true;
                            try {
                                const res = await http(this.url('assign', this.conv.id), 'PATCH', { assigned_to: userId ? Number(userId) : null });
                                this.applyConversation(res.conversation);
                                window.toastr && window.toastr.success(this.i18n.assigned);
                            } catch (e) { window.toastr && window.toastr.error(e.message); } finally { this.busy = false; }
                        },
                        applyConversation(c) {
                            if (!c) return;
                            if (this.conv && this.conv.id === c.id) this.conv = Object.assign({}, this.conv, c);
                            this.updateRow(c);
                        },

                        /* ---- list rows (server-rendered) ---- */
                        updateRow(c, opts = {}) {
                            const row = document.querySelector('[data-conv-row="' + c.id + '"]');
                            if (!row) { if (opts.refreshIfMissing) this.refreshList(); return; }
                            const set = (sel, val) => { const el = row.querySelector(sel); if (el && val != null) el.textContent = val; };
                            if (c.last_message_preview) set('[data-row-preview]', c.last_message_preview);
                            if (c.last_message_at) { const t = row.querySelector('[data-row-time]'); if (t) { t.setAttribute('datetime', c.last_message_at); t.textContent = this.relTime(c.last_message_at); } }
                            const unread = row.querySelector('[data-row-unread]');
                            if (unread) { unread.textContent = c.admin_unread; unread.style.display = c.admin_unread > 0 ? '' : 'none'; }
                            row.classList.toggle('is-unread', c.admin_unread > 0);
                            row.classList.toggle('is-closed', c.status !== 'open');
                            const dot = row.querySelector('[data-row-status]'); if (dot) { dot.classList.toggle('is-open', c.status === 'open'); dot.classList.toggle('is-closed', c.status !== 'open'); }
                            set('[data-row-assignee]', c.assignee ? c.assignee.name : this.i18n.unassigned);
                            if (opts.toTop) { const li = row.closest('li'); const ul = li && li.parentElement; if (ul && ul.firstElementChild !== li) ul.prepend(li); row.classList.remove('is-flash'); void row.offsetWidth; row.classList.add('is-flash'); }
                        },
                        relTime(iso) {
                            const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
                            if (s < 60) return 'now'; if (s < 3600) return Math.floor(s / 60) + 'm'; if (s < 86400) return Math.floor(s / 3600) + 'h'; return Math.floor(s / 86400) + 'd';
                        },
                        setStat(n) { const el = document.querySelector('[data-chat-stat-unread]'); if (el && typeof n === 'number') el.textContent = n; },
                        async refreshList() {
                            try {
                                const r = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                                const html = await r.text();
                                const doc = new DOMParser().parseFromString(html, 'text/html');
                                const fresh = doc.querySelector('[data-chat-list]'); const cur = document.querySelector('[data-chat-list]');
                                if (fresh && cur) { cur.innerHTML = fresh.innerHTML; window.Alpine && window.Alpine.initTree(cur); this.syncActiveRow(); }
                            } catch (e) { /* ignore */ }
                        },

                        /* ---- realtime ---- */
                        onFeedMessage(e) {
                            if (!e || !e.message) return;
                            const c = e.conversation || { id: e.conversation_id };
                            if (this.conv && this.conv.id === c.id) {
                                if (this.push(e.message)) { this.typingName = ''; this.scrollToBottom(false); }
                                const merged = Object.assign({}, c); if (e.message.sender_role === 'customer') merged.admin_unread = 0;
                                this.applyConversation(merged); this.updateRow(merged, { toTop: true });
                                if (e.message.sender_role === 'customer') this.markRead();
                            } else {
                                this.updateRow(c, { toTop: true, refreshIfMissing: true });
                            }
                            if (e.message.sender_role === 'customer' && (!this.conv || this.conv.id !== c.id) && window.adminChatFeed) {
                                this.setStat(parseInt((document.querySelector('[data-admin-chat-count]') || {}).textContent, 10) || 0);
                            }
                        },
                        onFeedConversation(e) {
                            if (!e || !e.conversation) return;
                            if (this.conv && this.conv.id === e.conversation.id && e.reason === 'read') {
                                this.messages.forEach((m) => { if (m.sender_role === 'staff' && !m.read_at) m.read_at = new Date().toISOString(); });
                            }
                            this.applyConversation(e.conversation);
                        },
                        joinChannel(id) {
                            if (!window.Echo) return;
                            try {
                                this.channel = window.Echo.private('chat.conversation.' + id)
                                    .listenForWhisper('typing', (e) => {
                                        if (!e || e.role === 'staff') return;
                                        this.typingName = e.name || this.i18n.customer;
                                        clearTimeout(this.typingTimer); this.typingTimer = setTimeout(() => { this.typingName = ''; }, 3000);
                                        this.scrollToBottom(false);
                                    });
                            } catch (e) { this.channel = null; }
                        },
                        leaveChannel() {
                            if (this.channel && window.Echo && this.conv) { try { window.Echo.leave('chat.conversation.' + this.conv.id); } catch (e) {} }
                            this.channel = null;
                        },
                        whisper() {
                            if (!this.channel || !this.channel.whisper) return;
                            const now = Date.now(); if (now - this.lastWhisper < 2000) return; this.lastWhisper = now;
                            try { this.channel.whisper('typing', { role: 'staff', name: this.me.name }); } catch (e) {}
                        },
                        async poll() {
                            if (document.hidden) return;
                            try {
                                if (this.conv) {
                                    const newest = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                                    const res = await http(this.url('messages', this.conv.id) + '?after_id=' + newest);
                                    let added = false, fromCustomer = false;
                                    (res.messages || []).forEach((m) => { if (this.push(m)) { added = true; if (m.sender_role === 'customer') fromCustomer = true; } });
                                    if (added) { this.scrollToBottom(false); this.markRead(); }
                                    if (fromCustomer && window.adminChatFeed && window.adminChatFeed.play) window.adminChatFeed.play();
                                }
                                const u = await http(this.urls.unread);
                                if (window.adminChatFeed) window.adminChatFeed.setUnread(u.unread);
                            } catch (e) { /* ignore */ }
                        },
                    };
                };
            </script>
        @endpush
    @endonce
</x-app-layout>
