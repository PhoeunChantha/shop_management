{{-- Cart slide-over (Bootstrap Offcanvas). Body + footer hydrated by main.js --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel"
     style="width:min(440px,100vw)">
    <div class="ut-offcanvas-head">
        <h3 id="cartDrawerLabel" style="font-size:19px">Your bag <span class="muted" id="cartDrawerCount" style="font-weight:500">(0)</span></h3>
        <button type="button" class="icon-btn" style="box-shadow:none;background:var(--bg)" data-bs-dismiss="offcanvas" aria-label="Close">
            <x-frontend.icon n="close" :size="18" />
        </button>
    </div>
    <div class="offcanvas-body" id="cartDrawerBody" style="padding:8px 22px"></div>
    <div id="cartDrawerFoot" style="padding:22px;border-top:1px solid var(--border);background:#fff;display:none"></div>
</div>
