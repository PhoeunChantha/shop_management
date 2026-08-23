{{-- Floating "back to top" button. Appears after the page is scrolled a bit; sits
     above the live-chat launcher and hides while the chat panel is open. --}}
<button type="button" class="ut-totop" data-back-to-top aria-label="{{ __('Back to top') }}" title="{{ __('Back to top') }}" tabindex="-1">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V6"/><path d="m6 12 6-6 6 6"/>
    </svg>
</button>
<script>
    (function () {
        var btn = document.querySelector('[data-back-to-top]');
        if (!btn) return;
        var ticking = false, shown = false;
        function update() {
            ticking = false;
            var show = (window.scrollY || document.documentElement.scrollTop) > 480;
            if (show !== shown) {
                shown = show;
                btn.classList.toggle('is-visible', show);
                btn.tabIndex = show ? 0 : -1;
            }
        }
        window.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
        }, { passive: true });
        btn.addEventListener('click', function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        });
        update();
    })();
</script>
