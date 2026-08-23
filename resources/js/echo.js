/**
 * Laravel Echo bootstrap for the admin bundle (Reverb websockets).
 *
 * Config comes from <meta name="reverb-*"> tags rendered by the admin layout so
 * the bundle stays environment-agnostic. When the tags are missing (e.g. a guest
 * page) or the connection cannot be made, `window.Echo` stays undefined and
 * every consumer falls back to polling — the admin never depends on sockets.
 *
 * Also wires the global admin live-chat feed (`private-admin.chat`) that keeps
 * the header badge live and toasts new customer messages on every admin page;
 * the inbox page itself takes over rendering via `window.adminChatFeed`.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

function meta(name) {
    const el = document.querySelector(`meta[name="${name}"]`);
    return el ? el.getAttribute('content') : null;
}

export function bootEcho() {
    const key = meta('reverb-key');
    if (!key || window.Echo) return window.Echo || null;

    try {
        window.Pusher = Pusher;
        const port = parseInt(meta('reverb-port') || '8080', 10);
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: meta('reverb-host') || window.location.hostname,
            wsPort: port,
            wssPort: port,
            forceTLS: (meta('reverb-scheme') || 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: {
                    'X-CSRF-TOKEN': meta('csrf-token') || '',
                },
            },
        });
        return window.Echo;
    } catch (e) {
        window.Echo = undefined;
        return null;
    }
}

/* ---------- alert sound (Web Audio synth — no audio files; Settings → Live Chat) ---------- */
const MUTE_KEY = 'admin-chat-muted';
let audioCtx = null;
function audio() {
    if (audioCtx) return audioCtx;
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    try { audioCtx = new Ctx(); } catch (e) { audioCtx = null; }
    return audioCtx;
}
['pointerdown', 'keydown', 'touchstart'].forEach((ev) => document.addEventListener(ev, () => {
    const c = audio(); if (c && c.state === 'suspended') c.resume().catch(() => {});
}, { once: true, passive: true }));
function tone(ctx, freq, start, dur, type, gain) {
    const o = ctx.createOscillator(); const g = ctx.createGain();
    o.type = type || 'sine'; o.frequency.setValueAtTime(freq, start);
    g.gain.setValueAtTime(0.0001, start);
    g.gain.exponentialRampToValueAtTime(gain, start + 0.012);
    g.gain.exponentialRampToValueAtTime(0.0001, start + dur);
    o.connect(g); g.connect(ctx.destination); o.start(start); o.stop(start + dur + 0.05);
}
export function isChatMuted() {
    try { return localStorage.getItem(MUTE_KEY) === '1'; } catch (e) { return false; }
}
export function setChatMuted(muted) {
    try { localStorage.setItem(MUTE_KEY, muted ? '1' : '0'); } catch (e) { /* ignore */ }
    document.dispatchEvent(new CustomEvent('admin-chat:mute', { detail: { muted: !!muted } }));
}
export function playChatAlert(kindOverride) {
    const kind = kindOverride || meta('admin-chat-sound') || 'chime';
    if (kind === 'off' || (!kindOverride && isChatMuted())) return;
    const ctx = audio(); if (!ctx) return;
    const go = () => {
        const v = Math.max(0, Math.min(1, (parseInt(meta('admin-chat-volume') || '75', 10) || 0) / 100)) * 0.35;
        if (v <= 0) return;
        const t = ctx.currentTime + 0.01;
        if (kind === 'chime') { tone(ctx, 659.25, t, 0.28, 'sine', v); tone(ctx, 880, t + 0.14, 0.42, 'sine', v); }
        else if (kind === 'ding') { tone(ctx, 1046.5, t, 0.7, 'sine', v); tone(ctx, 2093, t, 0.35, 'sine', v * 0.25); }
        else { tone(ctx, 620, t, 0.16, 'triangle', v); }
    };
    if (ctx.state === 'suspended') ctx.resume().then(go).catch(() => {}); else go();
}

/**
 * Global admin chat feed: keeps [data-admin-chat-count] badges live and toasts
 * new customer messages. Listeners registered via window.adminChatFeed.on(...)
 * (the inbox page) receive every event too.
 */
export function bootAdminChatFeed() {
    if (meta('admin-chat') !== '1') return;

    const listeners = { message: [], conversation: [] };
    const feed = {
        play: playChatAlert, isMuted: isChatMuted, setMuted: setChatMuted,
        on(type, fn) { (listeners[type] || (listeners[type] = [])).push(fn); return () => feed.off(type, fn); },
        off(type, fn) { listeners[type] = (listeners[type] || []).filter((f) => f !== fn); },
        emit(type, payload) { (listeners[type] || []).forEach((fn) => { try { fn(payload); } catch (e) { /* isolate */ } }); },
        setUnread(n) {
            const count = Math.max(0, Number(n) || 0);
            document.querySelectorAll('[data-admin-chat-count]').forEach((el) => {
                el.textContent = count > 99 ? '99+' : String(count);
                el.classList.toggle('d-none', count === 0);
            });
        },
        bump(delta) {
            const el = document.querySelector('[data-admin-chat-count]');
            const current = el && !el.classList.contains('d-none') ? parseInt(el.textContent, 10) || 0 : 0;
            feed.setUnread(current + delta);
        },
        inboxActive: false,
        live: false,
    };
    window.adminChatFeed = feed;
    // Consumers (the inbox page) may have initialised before this lazy module
    // loaded — tell them the feed exists once its transport is decided.
    const announce = () => document.dispatchEvent(new CustomEvent('admin-chat:ready', { detail: { feed } }));

    const echo = bootEcho();
    if (!echo) { announce(); return; }

    try {
        echo.private('admin.chat')
            .listen('.chat.message', (e) => {
                feed.emit('message', e);
                if (e && e.message && e.message.sender_role === 'customer') playChatAlert();
                if (e && e.message && e.message.sender_role === 'customer' && !feed.inboxActive) {
                    feed.bump(1);
                    if (window.toastr) {
                        const who = (e.conversation && e.conversation.customer && e.conversation.customer.name) || 'Customer';
                        const url = meta('admin-chat-url');
                        const body = String(e.message.body || '').slice(0, 90);
                        window.toastr.info(
                            `<strong>${escapeHtml(who)}</strong><br>${escapeHtml(body)}`,
                            null,
                            { escapeHtml: false, timeOut: 6000, onclick: url ? () => { window.location.href = `${url}?conversation=${e.conversation_id}`; } : undefined }
                        );
                    }
                }
            })
            .listen('.chat.conversation', (e) => feed.emit('conversation', e));
        feed.live = true;
    } catch (e) {
        feed.live = false;
    }
    announce();
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
