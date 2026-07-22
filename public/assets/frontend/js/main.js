/* ============================================================
   URBAN THREAD — frontend interactions (plain JS, no framework)
   Cart + wishlist persist in localStorage. Drawers use Bootstrap
   Offcanvas; quick-view uses Bootstrap Modal.
   ============================================================ */
(function () {
  'use strict';

  const money = (n) => '$' + (Number.isInteger(n) ? n : Number(n).toFixed(2));
  const SHIP_FREE = 75;
  const COLORS = window.UT_COLORS || {};
  const colorName = (k) => (COLORS[k] && COLORS[k].name) || k;
  const colorHex  = (k) => (COLORS[k] && COLORS[k].hex) || '#ccc';

  /* ---------- storage ---------- */
  const store = {
    get cart() { try { return JSON.parse(localStorage.getItem('ut_cart') || '[]'); } catch (e) { return []; } },
    set cart(v) { localStorage.setItem('ut_cart', JSON.stringify(v)); },
    get wish() { try { return JSON.parse(localStorage.getItem('ut_wish') || '[]'); } catch (e) { return []; } },
    set wish(v) { localStorage.setItem('ut_wish', JSON.stringify(v)); },
  };

  /* ---------- toast ---------- */
  let toastTimer;
  function toast(msg) {
    document.querySelectorAll('.toast').forEach(t => t.remove());
    const el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5L16 9"/></svg>' + msg;
    document.body.appendChild(el);
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.remove(), 2400);
  }
  window.utToast = toast;

  /* ---------- cart ---------- */
  function cartCount() { return store.cart.reduce((n, i) => n + i.qty, 0); }
  function cartSubtotal() { return store.cart.reduce((s, i) => s + i.price * i.qty, 0); }

  function addToCart(item) {
    const cart = store.cart;
    const key = item.id + '-' + item.size + '-' + item.color;
    const ex = cart.find(i => i.key === key);
    if (ex) ex.qty += item.qty; else cart.push(Object.assign({ key: key }, item));
    store.cart = cart;
    syncBadges();
    renderCartDrawer();
  }
  function updateQty(key, qty) {
    const cart = store.cart.map(i => i.key === key ? Object.assign({}, i, { qty: Math.max(1, qty) }) : i);
    store.cart = cart; syncBadges(); renderCartDrawer(); renderCartPage();
  }
  function removeItem(key) {
    store.cart = store.cart.filter(i => i.key !== key);
    syncBadges(); renderCartDrawer(); renderCartPage();
  }

  function syncBadges() {
    const c = cartCount();
    document.querySelectorAll('[data-cart-count]').forEach(el => {
      el.textContent = c;
      el.style.display = c > 0 ? '' : 'none';
    });
    const w = store.wish.length;
    document.querySelectorAll('[data-wish-count]').forEach(el => {
      el.textContent = w;
      el.style.display = w > 0 ? '' : 'none';
    });
  }

  function lineHTML(it) {
    return '' +
      '<div class="ut-row" style="gap:14px;padding:14px 0;border-bottom:1px solid var(--border-2);align-items:flex-start">' +
        '<div class="ph" style="width:72px;height:90px;border-radius:14px;flex-shrink:0;--ph-tint:' + it.tint + '"></div>' +
        '<div style="flex:1;min-width:0">' +
          '<div class="ut-row" style="justify-content:space-between;gap:8px">' +
            '<div style="font-family:var(--font-head);font-weight:600;font-size:14.5px">' + it.name + '</div>' +
            '<button data-remove="' + it.key + '" style="border:0;background:none;color:var(--text-3);padding:2px" aria-label="Remove">' +
              '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>' +
            '</button>' +
          '</div>' +
          '<div class="ut-row muted" style="gap:8px;font-size:13px;margin:5px 0 10px">' +
            '<span class="ut-row" style="gap:5px"><span class="swatch" style="width:14px;height:14px;background:' + colorHex(it.color) + '"></span>' + colorName(it.color) + '</span>' +
            '<span>·</span><span>Size ' + it.size + '</span>' +
          '</div>' +
          '<div class="ut-row" style="justify-content:space-between">' +
            qtyHTML(it.key, it.qty) +
            '<span style="font-family:var(--font-head);font-weight:700;font-size:15px">' + money(it.price * it.qty) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>';
  }
  function qtyHTML(key, qty) {
    return '<div class="ut-row" style="border:1.5px solid var(--border);border-radius:var(--r-pill);overflow:hidden;background:#fff">' +
      '<button data-qty="' + key + '" data-d="-1" style="border:0;background:none;padding:6px 11px;color:var(--ink)">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M5 12h14"/></svg></button>' +
      '<span style="font-family:var(--font-head);font-weight:600;min-width:24px;text-align:center">' + qty + '</span>' +
      '<button data-qty="' + key + '" data-d="1" style="border:0;background:none;padding:6px 11px;color:var(--ink)">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button>' +
    '</div>';
  }

  function renderCartDrawer() {
    const body = document.getElementById('cartDrawerBody');
    const foot = document.getElementById('cartDrawerFoot');
    const head = document.getElementById('cartDrawerCount');
    if (!body) return;
    const cart = store.cart;
    if (head) head.textContent = '(' + cart.length + ')';
    if (cart.length === 0) {
      body.innerHTML = '<div style="text-align:center;padding:50px 20px"><div style="width:70px;height:70px;border-radius:20px;background:var(--bg);display:grid;place-items:center;margin:0 auto 18px;color:var(--text-2)">' +
        '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg></div>' +
        '<h3>Your bag is empty</h3><p class="muted" style="margin:8px 0 20px">Add some heavyweight essentials.</p>' +
        '<a href="' + (window.UT_URLS ? window.UT_URLS.shop : '#') + '" class="ut-btn ut-btn-ink">Start shopping</a></div>';
      if (foot) foot.style.display = 'none';
      return;
    }
    const sub = cartSubtotal();
    const remain = Math.max(0, SHIP_FREE - sub);
    const pct = Math.min(100, (sub / SHIP_FREE) * 100);
    body.innerHTML =
      '<div style="padding:14px 0;border-bottom:1px solid var(--border);margin-bottom:8px">' +
        (remain > 0
          ? '<p style="font-size:13.5px;margin:0 0 8px">You\'re <b>' + money(remain) + '</b> away from free shipping</p>'
          : '<p style="font-size:13.5px;margin:0 0 8px;color:#15803d;font-weight:600">✓ You unlocked free shipping!</p>') +
        '<div style="height:7px;background:var(--bg);border-radius:7px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:' + (remain > 0 ? 'var(--blue)' : 'var(--success)') + ';transition:width .4s"></div></div>' +
      '</div>' +
      cart.map(lineHTML).join('');
    if (foot) {
      foot.style.display = '';
      foot.innerHTML =
        '<div class="ut-row" style="justify-content:space-between;margin-bottom:4px"><span class="muted">Subtotal</span>' +
        '<span style="font-family:var(--font-head);font-weight:700;font-size:20px">' + money(sub) + '</span></div>' +
        '<p class="muted" style="font-size:12.5px;margin:0 0 14px">Shipping &amp; taxes calculated at checkout.</p>' +
        '<a href="' + (window.UT_URLS ? window.UT_URLS.checkout : '#') + '" class="ut-btn ut-btn-accent ut-btn-block ut-btn-lg">Checkout</a>' +
        '<a href="' + (window.UT_URLS ? window.UT_URLS.cart : '#') + '" class="ut-btn ut-btn-ghost ut-btn-block" style="margin-top:10px">View full bag</a>';
    }
  }

  /* ---------- cart page (server-rendered shell, JS fills lines + totals) ---------- */
  function renderCartPage() {
    const wrap = document.getElementById('cartPageLines');
    if (!wrap) return;
    const cart = store.cart;
    const empty = document.getElementById('cartEmpty');
    const grid = document.getElementById('cartGrid');
    if (cart.length === 0) {
      if (empty) empty.style.display = '';
      if (grid) grid.style.display = 'none';
      return;
    }
    if (empty) empty.style.display = 'none';
    if (grid) grid.style.display = '';
    wrap.innerHTML = cart.map(lineHTML).join('');
    const sub = cartSubtotal();
    const discount = window.UT_COUPON ? Math.round(sub * 0.1 * 100) / 100 : 0;
    const shipping = sub === 0 ? 0 : (sub - discount >= SHIP_FREE ? 0 : 6.95);
    const tax = Math.round((sub - discount) * 0.08 * 100) / 100;
    const total = Math.max(0, sub - discount) + shipping + tax;
    setText('sumSubtotal', money(sub));
    setText('sumShipping', shipping === 0 ? 'Free' : money(shipping));
    setText('sumTax', money(tax));
    setText('sumTotal', money(total));
    const dRow = document.getElementById('sumDiscountRow');
    if (dRow) { dRow.style.display = discount > 0 ? '' : 'none'; setText('sumDiscount', '-' + money(discount)); }
    const cntEl = document.getElementById('cartItemCount');
    if (cntEl) cntEl.textContent = cartCount();
  }
  function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

  /* ---------- wishlist ---------- */
  function toggleWish(id) {
    id = Number(id);
    let wish = store.wish;
    if (wish.includes(id)) wish = wish.filter(x => x !== id); else wish.push(id);
    store.wish = wish;
    syncWishButtons(); syncBadges();
  }
  function syncWishButtons() {
    const wish = store.wish;
    document.querySelectorAll('[data-wish]').forEach(btn => {
      const on = wish.includes(Number(btn.getAttribute('data-wish')));
      btn.classList.toggle('is-on', on);
      const path = btn.querySelector('svg path');
      if (path) path.setAttribute('fill', on ? 'currentColor' : 'none');
    });
  }

  /* ============================================================
     EVENT WIRING
     ============================================================ */
  document.addEventListener('click', function (e) {
    const add = e.target.closest('[data-add-to-cart]');
    if (add) {
      e.preventDefault(); e.stopPropagation();
      const ds = add.dataset;
      // size/color may come from selected controls on PDP, else defaults
      const scope = add.closest('[data-product-scope]') || document;
      const sizeEl = scope.querySelector('[data-size].is-active') || scope.querySelector('[data-size]');
      const colorEl = scope.querySelector('[data-color].is-active');
      const qtyEl = scope.querySelector('[data-qty-value]');
      if (add.hasAttribute('data-require-size') && !sizeEl && !ds.size) { toast('Please select a size'); return; }
      addToCart({
        id: Number(ds.id), name: ds.name, price: Number(ds.price), tint: ds.tint || 'linear-gradient(150deg,#eef2f7,#e2e8f0)',
        size: sizeEl ? sizeEl.getAttribute('data-size') : (ds.size || 'M'),
        color: colorEl ? colorEl.getAttribute('data-color') : (ds.color || 'black'),
        qty: qtyEl ? Number(qtyEl.textContent) : 1,
      });
      toast(ds.name + ' added to bag');
      if (!add.hasAttribute('data-no-open')) openOffcanvas('cartDrawer');
      return;
    }

    const wishBtn = e.target.closest('[data-wish]');
    if (wishBtn) { e.preventDefault(); e.stopPropagation(); toggleWish(wishBtn.getAttribute('data-wish')); return; }

    const rm = e.target.closest('[data-remove]');
    if (rm) { e.preventDefault(); removeItem(rm.getAttribute('data-remove')); return; }

    const q = e.target.closest('[data-qty]');
    if (q) {
      e.preventDefault();
      const key = q.getAttribute('data-qty'); const d = Number(q.getAttribute('data-d'));
      const it = store.cart.find(i => i.key === key);
      if (it) updateQty(key, it.qty + d);
      return;
    }

    // size / color selectors (PDP, quick view)
    const sz = e.target.closest('[data-size]');
    if (sz && sz.closest('[data-size-group]')) {
      sz.closest('[data-size-group]').querySelectorAll('[data-size]').forEach(b => b.classList.remove('is-active'));
      sz.classList.add('is-active');
      return;
    }
    const col = e.target.closest('[data-color]');
    if (col && col.closest('[data-color-group]')) {
      col.closest('[data-color-group]').querySelectorAll('[data-color]').forEach(b => b.classList.remove('is-active'));
      col.classList.add('is-active');
      const lbl = document.querySelector('[data-color-label]');
      if (lbl) lbl.textContent = colorName(col.getAttribute('data-color'));
      return;
    }

    // PDP qty stepper (local, not cart)
    const lq = e.target.closest('[data-qty-step]');
    if (lq) {
      e.preventDefault();
      const box = lq.closest('[data-product-scope]') || document;
      const val = box.querySelector('[data-qty-value]');
      if (val) val.textContent = Math.max(1, Number(val.textContent) + Number(lq.getAttribute('data-qty-step')));
      return;
    }
  });

  function openOffcanvas(id) {
    const el = document.getElementById(id);
    if (el && window.bootstrap) bootstrap.Offcanvas.getOrCreateInstance(el).show();
  }

  /* ---------- coupon (cart page) ---------- */
  document.addEventListener('submit', function (e) {
    if (e.target.matches('#couponForm')) {
      e.preventDefault();
      const code = (e.target.querySelector('input').value || '').trim().toUpperCase();
      if (code === 'URBAN10') { window.UT_COUPON = true; toast('Coupon applied — 10% off'); renderCartPage();
        const ok = document.getElementById('couponApplied'); if (ok) ok.style.display = 'flex';
      } else toast('Invalid coupon code');
    }
  });

  /* ---------- header scroll ---------- */
  const header = document.querySelector('.ut-header');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 8);
    window.addEventListener('scroll', onScroll); onScroll();
  }

  /* ---------- sticky add-to-cart bar (PDP) ---------- */
  const sticky = document.querySelector('.ut-stickybar');
  if (sticky) {
    window.addEventListener('scroll', () => sticky.classList.toggle('show', window.scrollY > 520));
  }

  /* ---------- countdown (flash sale) ---------- */
  document.querySelectorAll('[data-countdown]').forEach(node => {
    let t = Math.max(0, Math.floor(Number(node.getAttribute('data-countdown')) || 0));
    const h = node.querySelector('[data-h]'), m = node.querySelector('[data-m]'), s = node.querySelector('[data-s]');
    const tick = () => {
      if (t > 0) t--;
      if (h) h.textContent = String(Math.floor(t / 3600)).padStart(2, '0');
      if (m) m.textContent = String(Math.floor((t % 3600) / 60)).padStart(2, '0');
      if (s) s.textContent = String(Math.floor(t % 60)).padStart(2, '0');
    };
    tick(); setInterval(tick, 1000);
  });

  /* ---------- FAQ accordion ---------- */
  document.querySelectorAll('.ut-acc-q').forEach(q => {
    q.addEventListener('click', () => {
      const open = q.getAttribute('aria-expanded') === 'true';
      const ans = q.nextElementSibling;
      q.setAttribute('aria-expanded', String(!open));
      if (ans) ans.style.display = open ? 'none' : 'block';
    });
  });

  /* ---------- OTP inputs ---------- */
  const otp = document.getElementById('otpGroup');
  if (otp) {
    const boxes = [].slice.call(otp.querySelectorAll('input'));
    boxes.forEach((box, i) => {
      box.addEventListener('input', () => {
        box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
        box.style.borderColor = box.value ? 'var(--ink)' : 'var(--border)';
        if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
        const btn = document.getElementById('otpVerify');
        if (btn) btn.disabled = !boxes.every(b => b.value);
      });
      box.addEventListener('keydown', (ev) => { if (ev.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus(); });
    });
    if (boxes[0]) boxes[0].focus();
    let secs = 38; const rs = document.getElementById('otpResend');
    if (rs) { const t = setInterval(() => { secs--; rs.textContent = '0:' + String(Math.max(0, secs)).padStart(2, '0'); if (secs <= 0) { clearInterval(t); rs.textContent = 'Resend code'; } }, 1000); }
  }

  /* ---------- password show/hide ---------- */
  document.querySelectorAll('[data-toggle-pw]').forEach(btn => {
    btn.addEventListener('click', () => {
      const inp = btn.parentNode.querySelector('input');
      if (!inp) return;
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
    });
  });

  /* ---------- multi-step checkout ---------- */
  const checkout = document.getElementById('checkoutSteps');
  if (checkout) {
    let step = 0;
    const panels = [].slice.call(checkout.querySelectorAll('[data-step]'));
    const dots = [].slice.call(document.querySelectorAll('[data-step-dot]'));
    const show = () => {
      panels.forEach((p, i) => p.style.display = i === step ? '' : 'none');
      dots.forEach((d, i) => {
        d.classList.toggle('done', i < step);
        d.classList.toggle('current', i === step);
        d.style.background = i < step ? 'var(--success)' : i === step ? 'var(--ink)' : 'var(--bg)';
        d.style.color = i <= step ? '#fff' : 'var(--text-2)';
      });
      const back = document.getElementById('coBack');
      if (back) back.style.visibility = step > 0 ? 'visible' : 'hidden';
    };
    document.addEventListener('click', (e) => {
      if (e.target.closest('#coNext')) {
        if (step < panels.length - 1) { step++; show(); }
        else {
          var coForm = document.getElementById('checkoutForm');
          if (coForm) {
            var itemsInput = document.getElementById('coItems');
            if (itemsInput) itemsInput.value = JSON.stringify(store.cart);
            coForm.submit();
          } else {
            window.location.href = window.UT_URLS.confirm;
          }
        }
      }
      if (e.target.closest('#coBack')) { if (step > 0) { step--; show(); } }
    });
    show();
  }

  /* ---------- review star rating ---------- */
  const rate = document.getElementById('reviewStars');
  if (rate) {
    const stars = [].slice.call(rate.querySelectorAll('[data-star]'));
    let val = 0;
    const paint = (n) => stars.forEach((s, i) => s.classList.toggle('on', i < n));
    stars.forEach((s, i) => {
      s.addEventListener('mouseenter', () => paint(i + 1));
      s.addEventListener('click', () => { val = i + 1; paint(val); const f = document.getElementById('ratingVal'); if (f) f.value = val; });
    });
    rate.addEventListener('mouseleave', () => paint(val));
  }

  /* ---------- homepage hero carousel fallback ---------- */
  const heroCarousel = document.getElementById('utHeroCarousel');
  if (heroCarousel) {
    const slides = Array.from(heroCarousel.querySelectorAll('.ut-hero-item'));
    const dots = Array.from(heroCarousel.querySelectorAll('[data-hero-slide]'));
    const previous = heroCarousel.querySelector('[data-hero-action="prev"]');
    const next = heroCarousel.querySelector('[data-hero-action="next"]');
    let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('active')));
    let intervalId;

    const showSlide = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => slide.classList.toggle('active', slideIndex === activeIndex));
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('active', dotIndex === activeIndex);
        dot.setAttribute('aria-current', dotIndex === activeIndex ? 'true' : 'false');
      });
    };

    const restart = () => {
      window.clearInterval(intervalId);
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        intervalId = window.setInterval(() => showSlide(activeIndex + 1), 5000);
      }
    };

    previous?.addEventListener('click', (event) => { event.preventDefault(); showSlide(activeIndex - 1); restart(); });
    next?.addEventListener('click', (event) => { event.preventDefault(); showSlide(activeIndex + 1); restart(); });
    dots.forEach((dot, index) => dot.addEventListener('click', (event) => { event.preventDefault(); showSlide(index); restart(); }));

    let touchStartX = 0;
    heroCarousel.addEventListener('touchstart', (event) => { touchStartX = event.changedTouches[0].screenX; }, { passive: true });
    heroCarousel.addEventListener('touchend', (event) => {
      const distance = event.changedTouches[0].screenX - touchStartX;
      if (Math.abs(distance) > 45) {
        showSlide(activeIndex + (distance < 0 ? 1 : -1));
        restart();
      }
    }, { passive: true });

    heroCarousel.addEventListener('mouseenter', () => window.clearInterval(intervalId));
    heroCarousel.addEventListener('mouseleave', restart);
    showSlide(activeIndex);
    restart();
  }

  /* ---------- community testimonial slider ---------- */
  const testimonialSlider = document.querySelector('.ut-testimonial-slider');
  if (testimonialSlider) {
    const track = testimonialSlider.querySelector('.ut-testimonial-track');
    const cards = Array.from(track.querySelectorAll('.ut-testimonial-card'));
    const dots = Array.from(document.querySelectorAll('[data-testimonial-dot]'));
    const previous = document.querySelector('[data-testimonial-action="prev"]');
    const next = document.querySelector('[data-testimonial-action="next"]');
    let activeIndex = 0;
    let timerId;

    const slideMetrics = () => {
      const cardWidth = cards[0].getBoundingClientRect().width;
      const gap = parseFloat(getComputedStyle(track).gap) || 0;
      const visibleCards = Math.max(1, Math.round(testimonialSlider.clientWidth / (cardWidth + gap)));

      return { cardWidth, gap, maxStart: Math.max(0, cards.length - visibleCards) };
    };

    const showReview = (index) => {
      const { cardWidth, gap, maxStart } = slideMetrics();
      activeIndex = ((index % (maxStart + 1)) + (maxStart + 1)) % (maxStart + 1);
      track.style.transform = `translateX(-${activeIndex * (cardWidth + gap)}px)`;
      dots.forEach((dot, dotIndex) => {
        dot.style.display = dotIndex <= maxStart ? '' : 'none';
        dot.classList.toggle('active', dotIndex === activeIndex);
      });
    };

    const restart = () => {
      window.clearInterval(timerId);
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        timerId = window.setInterval(() => showReview(activeIndex + 1), 5500);
      }
    };

    previous?.addEventListener('click', () => { showReview(activeIndex - 1); restart(); });
    next?.addEventListener('click', () => { showReview(activeIndex + 1); restart(); });
    dots.forEach((dot, index) => dot.addEventListener('click', () => { showReview(index); restart(); }));
    testimonialSlider.addEventListener('mouseenter', () => window.clearInterval(timerId));
    testimonialSlider.addEventListener('mouseleave', restart);
    window.addEventListener('resize', () => showReview(activeIndex));

    let touchStartX = 0;
    testimonialSlider.addEventListener('touchstart', (event) => { touchStartX = event.changedTouches[0].screenX; }, { passive: true });
    testimonialSlider.addEventListener('touchend', (event) => {
      const distance = event.changedTouches[0].screenX - touchStartX;
      if (Math.abs(distance) > 45) {
        showReview(activeIndex + (distance < 0 ? 1 : -1));
        restart();
      }
    }, { passive: true });

    showReview(0);
    restart();
  }

  /* ============================================================
     SCROLL REVEAL — IntersectionObserver, GPU transforms only.
     Targets [data-reveal] (authored hooks) + every .ut-pcard (so all
     product grids animate site-wide). Hidden-states live in CSS behind
     html.ut-anim, which the <head> sets only when motion is allowed.
     ============================================================ */
  (function initReveal() {
    const root = document.documentElement;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // We're alive: cancel the head failsafe that would force-reveal everything.
    if (window.__utRevealFailsafe) { clearTimeout(window.__utRevealFailsafe); window.__utRevealFailsafe = null; }

    if (reduce || !root.classList.contains('ut-anim') || !('IntersectionObserver' in window)) {
      root.classList.add('ut-no-anim'); // reveal all, no motion
      return;
    }

    const targets = Array.from(document.querySelectorAll('[data-reveal], .ut-pcard'))
      // Shop listing cards have their own reveal animation — don't observe them twice.
      .filter((el) => !(el.classList.contains('ut-pcard') && el.closest('.product-cell')));
    if (!targets.length) return;

    // Stagger siblings that share a parent so grids/rows cascade.
    const byParent = new Map();
    targets.forEach((el) => {
      const p = el.parentElement;
      if (!byParent.has(p)) byParent.set(p, []);
      byParent.get(p).push(el);
    });
    byParent.forEach((els) => {
      els.forEach((el, i) => { el.style.transitionDelay = Math.min(i * 65, 320) + 'ms'; });
    });

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        el.classList.add('is-visible');
        io.unobserve(el);
        // Drop will-change + the stagger delay once settled (keeps compositor lean,
        // and prevents the delay from lingering on later hover transitions).
        const done = () => { el.classList.add('reveal-done'); el.style.transitionDelay = ''; el.removeEventListener('transitionend', done); };
        el.addEventListener('transitionend', done);
        setTimeout(done, 1200);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

    targets.forEach((el) => io.observe(el));
  })();

  /* Hero product card now floats via CSS (heroFloat keyframes) so no JS tilt
     is needed — a JS transform would fight the CSS animation. */

  /* ---------- init ---------- */
  syncBadges();
  syncWishButtons();
  renderCartDrawer();
  renderCartPage();
})();
