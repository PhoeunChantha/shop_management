{{-- Social-login buttons — rendered from the enabled providers in
     Settings → Social Login. Renders nothing when none are enabled. --}}
@inject('settingsService', 'App\Services\SettingService')
@php($socialProviders = $settingsService->socialProviders())

@if (count($socialProviders))
    <div class="ut-row" style="gap:12px">
        @foreach ($socialProviders as $provider)
            <button type="button" class="ut-btn ut-btn-ghost" style="flex:1"
                onclick="utToast('Continue with {{ $provider['name'] }}')">
                <x-frontend.icon :n="$provider['icon']" :size="18" /> {{ $provider['name'] }}
            </button>
        @endforeach
    </div>
    <div class="ut-row" style="gap:14px;margin:22px 0">
        <hr class="divider" style="flex:1">
        <span class="muted" style="font-size:12.5px;font-family:var(--font-head);font-weight:600">OR</span>
        <hr class="divider" style="flex:1">
    </div>
@endif
