/* T-SHIRT SHOP — live chat (customer side).
 *
 * One shared thread state powers every mount on the page: the floating widget
 * (<x-frontend.chat-widget>) and the Account → Messages page. Each mount is a
 * root element carrying [data-chat-thread] with the DOM contract below.
 *
 *   [data-chat-messages]   scrollable list
 *   [data-chat-more]       "load earlier" button
 *   [data-chat-form]       composer form   → [data-chat-input] textarea, [data-chat-send] button
 *   [data-chat-typing]     typing indicator line
 *   [data-chat-status]     "Open"/"Closed" pill text
 *   [data-chat-empty]      empty-state block
 *
 * Transport: Laravel Echo over Reverb when window.Echo exists (wired in the
 * storefront layout), otherwise an 8 s poll of the JSON endpoints. Either way the
 * REST endpoints are the source of truth, so the feature degrades gracefully.
 */
(function () {
  'use strict';

  const CFG = window.UT_CHAT;
  if (!CFG || !CFG.urls) return;

  const CSRF = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
  const T = Object.assign({
    today: 'Today', yesterday: 'Yesterday', seen: 'Seen', sent: 'Sent',
    typing: 'is typing…', you: 'You', team: 'Support', open: 'Open', closed: 'Closed',
    failed: 'Could not send — try again.', loadEarlier: 'Load earlier messages',
    product: 'Product', askPrefill: 'Hi! I have a question about this product.',
  }, CFG.i18n || {});

  const state = {
    id: CFG.id,
    status: CFG.status || 'open',
    unread: Number(CFG.unread || 0),
    messages: [],           // oldest → newest
    ids: new Set(),
    hasMore: false,
    loaded: false,
    loading: false,
    visible: false,         // at least one mount is visible on screen
    mounts: [],
    channel: null,
    poll: null,
    typingTimer: null,
    lastWhisper: 0,
    readTimer: null,
    product: null,          // attached product for the next message ("Ask about this product")
    widgetOpen: null,       // set by initWidget()
  };

  /* ---------- http ---------- */
  function http(url, method, data) {
    const opts = {
      method: method || 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
    };
    if (data && method !== 'GET') {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(data);
    }
    return fetch(url, opts).then(async (r) => {
      const json = await r.json().catch(() => ({}));
      if (!r.ok) { const e = new Error(json.message || r.statusText); e.status = r.status; e.data = json; throw e; }
      return json;
    });
  }
  function withQuery(url, params) {
    const u = new URL(url, window.location.origin);
    Object.keys(params || {}).forEach((k) => { if (params[k] != null) u.searchParams.set(k, params[k]); });
    return u.toString();
  }

  /* ---------- formatting ---------- */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function linkify(text) {
    // escape first, then wrap bare URLs
    return esc(text).replace(/(https?:\/\/[^\s<]+)/g, (m) => '<a href="' + m + '" target="_blank" rel="noopener nofollow">' + m + '</a>')
      .replace(/\n/g, '<br>');
  }
  function dayKey(d) { return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate(); }
  function dayLabel(d) {
    const now = new Date(); const y = new Date(); y.setDate(now.getDate() - 1);
    if (dayKey(d) === dayKey(now)) return T.today;
    if (dayKey(d) === dayKey(y)) return T.yesterday;
    return d.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
  }
  function timeLabel(d) { return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }); }
  function initials(name) {
    return String(name || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0] || '').join('').toUpperCase() || '?';
  }

  /* ---------- state mutations ---------- */
  function addMessage(m, opts) {
    if (!m || state.ids.has(m.id)) return false;
    state.ids.add(m.id);
    state.messages.push(m);
    state.messages.sort((a, b) => a.id - b.id);
    if (!(opts && opts.silent)) {
      render({ stick: true });
      if (m.sender_role !== 'customer') {
        if (state.visible && !document.hidden) scheduleRead();
        else setUnread(state.unread + 1);
        if (!state.visible && window.utToast) window.utToast((m.sender.name || T.team) + ': ' + String(m.body).slice(0, 60));
        pingLaunchers();
        playAlert();
      }
    }
    return true;
  }
  function prependMessages(list) {
    list.forEach((m) => { if (!state.ids.has(m.id)) { state.ids.add(m.id); state.messages.push(m); } });
    state.messages.sort((a, b) => a.id - b.id);
  }
  // The conversation row is created lazily (first open / first send); once we know its id, go live.
  function adoptConversation(c) {
    if (c && c.id && !state.id) { state.id = c.id; connect(); }
  }
  function applyConversation(c) {
    if (!c) return;
    if (c.status && c.status !== state.status) { state.status = c.status; renderStatus(); }
    if (typeof c.customer_unread === 'number') {
      // When the thread is open, we'll mark read ourselves; only sync the badge when closed.
      if (!state.visible) setUnread(c.customer_unread);
    }
  }
  function setUnread(n) {
    state.unread = Math.max(0, Number(n) || 0);
    document.querySelectorAll('[data-chat-count]').forEach((el) => {
      el.textContent = String(state.unread);
      el.style.display = state.unread > 0 ? '' : 'none';
    });
    document.querySelectorAll('[data-chat-launcher]').forEach((el) => el.classList.toggle('has-unread', state.unread > 0));
  }
  function pingLaunchers() {
    document.querySelectorAll('[data-chat-launcher]').forEach((el) => {
      el.classList.remove('is-pinging'); void el.offsetWidth; el.classList.add('is-pinging');
    });
  }
  function scheduleRead() {
    clearTimeout(state.readTimer);
    state.readTimer = setTimeout(markRead, 400);
  }
  function markRead() {
    if (!state.messages.some((m) => m.sender_role !== 'customer' && !m.read_at) && state.unread === 0) return;
    state.messages.forEach((m) => { if (m.sender_role !== 'customer' && !m.read_at) m.read_at = new Date().toISOString(); });
    setUnread(0);
    http(CFG.urls.read, 'POST', {}).catch(() => {});
  }

  /* ---------- rendering ---------- */
  function renderStatus() {
    state.mounts.forEach((mt) => {
      const el = mt.root.querySelector('[data-chat-status]');
      if (el) {
        el.textContent = state.status === 'open' ? T.open : T.closed;
        el.classList.toggle('is-closed', state.status !== 'open');
      }
      mt.root.classList.toggle('is-closed', state.status !== 'open');
    });
  }

  function buildHtml() {
    if (!state.messages.length) return '';
    let html = '';
    let lastDay = null, lastSender = null, lastTime = 0;
    const lastMine = [...state.messages].reverse().find((m) => m.sender_role === 'customer');

    state.messages.forEach((m) => {
      const d = new Date(m.created_at);
      const mine = m.sender_role === 'customer';
      const dk = dayKey(d);
      if (dk !== lastDay) {
        html += '<div class="ut-chat-day"><span>' + esc(dayLabel(d)) + '</span></div>';
        lastDay = dk; lastSender = null;
      }
      const grouped = lastSender === m.sender.id && (d - lastTime) < 5 * 60 * 1000;
      lastSender = m.sender.id; lastTime = d;

      html += '<div class="ut-chat-row ' + (mine ? 'is-mine' : 'is-theirs') + (grouped ? ' is-grouped' : '') + '" data-mid="' + m.id + '">';
      if (!mine) {
        html += '<span class="ut-chat-avatar" aria-hidden="true">' +
          (m.sender.avatar ? '<img src="' + esc(m.sender.avatar) + '" alt="">' : esc(initials(m.sender.name || T.team))) + '</span>';
      }
      html += '<div class="ut-chat-bubble-wrap">';
      if (!mine && !grouped) html += '<span class="ut-chat-name">' + esc(m.sender.name || T.team) + '</span>';
      if (m.product) html += productCard(m.product);
      html += '<div class="ut-chat-bubble">' + linkify(m.body) + '</div>';
      html += '<span class="ut-chat-meta"><time datetime="' + esc(m.created_at) + '">' + esc(timeLabel(d)) + '</time>';
      if (mine && lastMine && lastMine.id === m.id) html += ' · <em>' + esc(m.read_at ? T.seen : T.sent) + '</em>';
      html += '</span></div></div>';
    });
    return html;
  }

  function render(opts) {
    const html = buildHtml();
    state.mounts.forEach((mt) => {
      const list = mt.root.querySelector('[data-chat-messages]');
      const empty = mt.root.querySelector('[data-chat-empty]');
      const more = mt.root.querySelector('[data-chat-more]');
      if (!list) return;
      const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
      const prevHeight = list.scrollHeight;
      list.innerHTML = html;
      if (empty) empty.style.display = state.messages.length ? 'none' : '';
      if (more) more.style.display = state.hasMore ? '' : 'none';
      if (opts && opts.keepOffset) {
        list.scrollTop = list.scrollHeight - prevHeight + (opts.keepOffset === true ? 0 : opts.keepOffset);
      } else if ((opts && opts.stick) || nearBottom) {
        list.scrollTop = list.scrollHeight;
      }
    });
    renderStatus();
  }

  function showTyping(name) {
    state.mounts.forEach((mt) => {
      const el = mt.root.querySelector('[data-chat-typing]');
      if (!el) return;
      el.querySelector('[data-chat-typing-name]').textContent = name || T.team;
      el.classList.add('is-on');
    });
    clearTimeout(state.typingTimer);
    state.typingTimer = setTimeout(hideTyping, 3000);
  }
  function hideTyping() {
    state.mounts.forEach((mt) => { const el = mt.root.querySelector('[data-chat-typing]'); if (el) el.classList.remove('is-on'); });
  }

  /* ---------- loading ---------- */
  function loadInitial() {
    if (state.loaded || state.loading) return Promise.resolve();
    state.loading = true;
    return http(CFG.urls.feed).then((res) => {
      prependMessages(res.messages || []);
      state.hasMore = !!res.has_more;
      state.loaded = true;
      adoptConversation(res.conversation);
      applyConversation(res.conversation);
      render({ stick: true });
      if (state.visible) scheduleRead();
    }).catch(() => {}).finally(() => { state.loading = false; });
  }
  function loadEarlier(mt) {
    if (state.loading || !state.hasMore || !state.messages.length) return;
    state.loading = true;
    const list = mt.root.querySelector('[data-chat-messages]');
    const anchorOffset = list ? list.scrollTop : 0;
    http(withQuery(CFG.urls.feed, { before_id: state.messages[0].id })).then((res) => {
      prependMessages(res.messages || []);
      state.hasMore = !!res.has_more;
      render({ keepOffset: anchorOffset });
    }).catch(() => {}).finally(() => { state.loading = false; });
  }
  function pollOnce() {
    http(CFG.urls.state).then((res) => {
      applyConversation(res.conversation);
      const newest = state.messages.length ? state.messages[state.messages.length - 1].id : 0;
      if ((res.last_message_id || 0) > newest) {
        return http(withQuery(CFG.urls.feed, { after_id: newest })).then((feed) => {
          (feed.messages || []).forEach((m) => addMessage(m));
        });
      }
    }).catch(() => {});
  }

  /* ---------- sending ---------- */
  function send(mt) {
    const input = mt.root.querySelector('[data-chat-input]');
    const btn = mt.root.querySelector('[data-chat-send]');
    const body = (input.value || '').trim();
    if (!body || mt.sending) return;
    mt.sending = true; if (btn) btn.disabled = true; mt.root.classList.add('is-sending');
    const payload = { body };
    if (state.product && state.product.id) payload.product_id = state.product.id;
    http(CFG.urls.store, 'POST', payload).then((res) => {
      input.value = ''; autosize(input);
      setProduct(null);
      addMessage(res.message, { silent: true });
      adoptConversation(res.conversation);
      applyConversation(res.conversation);
      render({ stick: true });
    }).catch((e) => {
      const errs = (e.data && e.data.errors) || {};
      const msg = (errs.body && errs.body[0]) || (errs.product_id && errs.product_id[0]) || T.failed;
      if (errs.product_id) setProduct(null);
      if (window.utToast) window.utToast(msg);
    }).finally(() => {
      mt.sending = false; if (btn) btn.disabled = false; mt.root.classList.remove('is-sending'); input.focus();
    });
  }
  function autosize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
  }

  /* ---------- alert sound (Web Audio, no files; Settings → Live Chat) ---------- */
  const SOUND = Object.assign({ kind: 'pop', volume: 75 }, CFG.sound || {});
  let audioCtx = null;
  function audio() {
    if (audioCtx) return audioCtx;
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    try { audioCtx = new Ctx(); } catch (e) { audioCtx = null; }
    return audioCtx;
  }
  // Browsers only allow sound after a user gesture: warm the context on the first one.
  ['pointerdown', 'keydown', 'touchstart'].forEach((ev) => document.addEventListener(ev, () => { const c = audio(); if (c && c.state === 'suspended') c.resume().catch(() => {}); }, { once: true, passive: true }));
  function tone(ctx, freq, start, dur, type, gain) {
    const o = ctx.createOscillator(); const g = ctx.createGain();
    o.type = type || 'sine'; o.frequency.setValueAtTime(freq, start);
    g.gain.setValueAtTime(0.0001, start);
    g.gain.exponentialRampToValueAtTime(gain, start + 0.012);
    g.gain.exponentialRampToValueAtTime(0.0001, start + dur);
    o.connect(g); g.connect(ctx.destination); o.start(start); o.stop(start + dur + 0.05);
  }
  function playAlert() {
    if (!SOUND.kind || SOUND.kind === 'off') return;
    const ctx = audio(); if (!ctx) return;
    const go = () => {
      const v = Math.max(0, Math.min(1, (Number(SOUND.volume) || 0) / 100)) * 0.35;
      if (v <= 0) return;
      const t = ctx.currentTime + 0.01;
      if (SOUND.kind === 'chime') { tone(ctx, 659.25, t, 0.28, 'sine', v); tone(ctx, 880, t + 0.14, 0.42, 'sine', v); }
      else if (SOUND.kind === 'ding') { tone(ctx, 1046.5, t, 0.7, 'sine', v); tone(ctx, 2093, t, 0.35, 'sine', v * 0.25); }
      else { tone(ctx, 620, t, 0.16, 'triangle', v); }
    };
    if (ctx.state === 'suspended') ctx.resume().then(go).catch(() => {}); else go();
  }

  /* ---------- product attachment ("Ask about this product") ---------- */
  function productCard(p) {
    const img = p.image
      ? '<img src="' + esc(p.image) + '" alt="" loading="lazy">'
      : '<span class="ut-chat-product-ph" aria-hidden="true"></span>';
    return '<a class="ut-chat-product" href="' + esc(p.url || '#') + '" target="_blank" rel="noopener">' +
      img +
      '<span class="ut-chat-product-copy"><span class="ut-chat-product-kicker">' + esc(T.product) + '</span>' +
      '<strong>' + esc(p.name) + '</strong>' +
      (p.price_label ? '<small>' + esc(p.price_label) + '</small>' : '') + '</span></a>';
  }
  function setProduct(p) {
    state.product = p && p.id ? p : null;
    state.mounts.forEach((mt) => {
      const box = mt.root.querySelector('[data-chat-attach]');
      if (!box) return;
      if (!state.product) { box.hidden = true; return; }
      const img = box.querySelector('[data-chat-attach-img]');
      const ph = box.querySelector('[data-chat-attach-ph]');
      if (state.product.image) { img.src = state.product.image; img.hidden = false; if (ph) ph.hidden = true; }
      else { img.hidden = true; img.removeAttribute('src'); if (ph) ph.hidden = false; }
      box.querySelector('[data-chat-attach-name]').textContent = state.product.name || '';
      box.querySelector('[data-chat-attach-price]').textContent = state.product.price_label || '';
      const link = box.querySelector('[data-chat-attach-link]'); if (link) link.href = state.product.url || '#';
      box.hidden = false;
    });
  }
  // Entry point for product buttons: attach the product, open the widget, prefill a prompt.
  function askAbout(p, sourceEl) {
    if (!p || !p.id) return;
    setProduct(p);
    if (sourceEl) { sourceEl.classList.add('is-loading'); setTimeout(() => sourceEl.classList.remove('is-loading'), 900); }
    if (state.widgetOpen) state.widgetOpen();
    recomputeVisible();
    const mt = state.mounts.find((m) => m.visibleFn && m.visibleFn()) || state.mounts[0];
    const input = mt && mt.root.querySelector('[data-chat-input]');
    if (input) {
      if (!input.value.trim()) { input.value = T.askPrefill; autosize(input); }
      setTimeout(() => { input.focus(); input.setSelectionRange(input.value.length, input.value.length); }, 120);
    }
  }
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-chat-product]');
    if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    let p = null;
    try { p = JSON.parse(btn.getAttribute('data-chat-product') || 'null'); } catch (err) { p = null; }
    askAbout(p, btn);
  }, true);
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-chat-attach-remove]')) { e.preventDefault(); setProduct(null); }
  });
  function whisperTyping() {
    if (!state.channel || !state.channel.whisper) return;
    const now = Date.now();
    if (now - state.lastWhisper < 2000) return;
    state.lastWhisper = now;
    try { state.channel.whisper('typing', { role: 'customer', name: (CFG.me && CFG.me.name) || T.you }); } catch (e) { /* no-op */ }
  }

  /* ---------- mounting ---------- */
  function mount(root) {
    if (!root || root.__utChat) return;
    const mt = { root, sending: false, visibleFn: null };
    root.__utChat = mt;
    state.mounts.push(mt);

    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const more = root.querySelector('[data-chat-more]');
    const list = root.querySelector('[data-chat-messages]');

    if (form) form.addEventListener('submit', (e) => { e.preventDefault(); send(mt); });
    if (input) {
      input.addEventListener('input', () => { autosize(input); whisperTyping(); });
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(mt); }
      });
      autosize(input);
    }
    if (more) more.addEventListener('click', () => loadEarlier(mt));
    if (list) list.addEventListener('scroll', () => { if (list.scrollTop < 40 && state.hasMore) loadEarlier(mt); });

    // Visibility: a mount counts as visible when its root is displayed on screen.
    mt.visibleFn = () => root.offsetParent !== null && !root.closest('[data-chat-panel]:not(.is-open)');
    recomputeVisible();
    if (state.loaded) render({ stick: true });
  }
  function recomputeVisible() {
    const wasVisible = state.visible;
    state.visible = state.mounts.some((mt) => mt.visibleFn && mt.visibleFn());
    if (state.visible && !wasVisible) {
      loadInitial().then(() => { scheduleRead(); render({ stick: true }); });
      const first = state.mounts.find((mt) => mt.visibleFn());
      const input = first && first.root.querySelector('[data-chat-input]');
      if (input && window.matchMedia('(min-width: 768px)').matches) setTimeout(() => input.focus(), 60);
    }
  }

  /* ---------- widget open/close ---------- */
  function initWidget() {
    const panel = document.querySelector('[data-chat-panel]');
    if (!panel) return;
    const open = () => {
      panel.classList.add('is-open');
      panel.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('ut-chat-open');
      recomputeVisible();
    };
    const close = () => {
      panel.classList.remove('is-open');
      panel.setAttribute('aria-hidden', 'true');
      document.documentElement.classList.remove('ut-chat-open');
      recomputeVisible();
    };
    state.widgetOpen = open;
    document.querySelectorAll('[data-chat-launcher]').forEach((b) => b.addEventListener('click', (e) => {
      if (b.tagName === 'A') return; // guest link → login
      e.preventDefault();
      panel.classList.contains('is-open') ? close() : open();
    }));
    document.querySelectorAll('[data-chat-close]').forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && panel.classList.contains('is-open')) close(); });
  }

  /* ---------- realtime ---------- */
  function connect() {
    if (!state.id || state.channel || state.poll) return;
    if (window.Echo && typeof window.Echo.private === 'function') {
      try {
        state.channel = window.Echo.private('chat.conversation.' + state.id)
          .listen('.chat.message', (e) => { if (e && e.message) { addMessage(e.message); applyConversation(e.conversation); hideTyping(); } })
          .listen('.chat.conversation', (e) => { if (e && e.conversation) applyConversation(e.conversation); if (e && e.reason === 'read') { state.messages.forEach((m) => { if (m.sender_role === 'customer') m.read_at = m.read_at || new Date().toISOString(); }); render(); } })
          .listenForWhisper('typing', (e) => { if (!e || e.role === 'customer') return; showTyping(e.name); });
        // Safety net: occasional reconcile even with sockets (missed events after sleep/reconnect).
        state.poll = setInterval(() => { if (state.visible && !document.hidden) pollOnce(); }, 45000);
        return;
      } catch (e) { /* fall through to polling */ }
    }
    state.poll = setInterval(() => { if (!document.hidden) pollOnce(); }, 8000);
  }

  document.addEventListener('visibilitychange', () => { if (!document.hidden && state.visible) { scheduleRead(); pollOnce(); } });

  /* ---------- boot ---------- */
  function boot() {
    setUnread(state.unread);
    document.querySelectorAll('[data-chat-thread]').forEach(mount);
    initWidget();
    connect();
    recomputeVisible();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();

  window.UTChat = { mount, askAbout, setProduct, open: () => { if (state.widgetOpen) state.widgetOpen(); }, state };
})();
