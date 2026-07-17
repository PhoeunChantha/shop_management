@php
    $locale = app()->getLocale();
    $headerNotifications = $adminHeaderNotifications ?? collect();
    $unreadNotifications = $adminUnreadNotifications ?? 0;
@endphp

<div class="container-fluid py-2 px-4 d-flex justify-content-between align-items-center gap-3">

    <div class="page-title fs-5 fw-bold text-dark min-w-0">
        {{ $header }}
    </div>

    <div class="d-flex align-items-center text-secondary small flex-shrink-0 gap-2">

        {{-- Language switcher --}}
        <div class="language-switch d-none d-sm-inline-flex align-items-center p-1 rounded-pill">
            <a href="{{ route('lang.switch', 'km') }}"
                class="btn btn-sm border-0 px-2.5 py-0.5 rounded-pill fs-7 text-decoration-none {{ $locale === 'km' ? 'fw-bold bg-white shadow-sm text-dark' : 'fw-medium bg-transparent text-secondary' }}">
                ខ្មែរ
            </a>
            <a href="{{ route('lang.switch', 'en') }}"
                class="btn btn-sm border-0 px-2.5 py-0.5 rounded-pill fs-7 text-decoration-none {{ $locale === 'en' ? 'fw-bold bg-white shadow-sm text-dark' : 'fw-medium bg-transparent text-secondary' }}">
                EN
            </a>
        </div>

        {{-- Dark mode toggle --}}
        <button class="icon-button btn btn-link p-0 text-decoration-none fs-6 text-secondary" type="button" aria-label="Toggle dark mode"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('admin-theme', dark ? 'dark' : 'light')"
            :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
            <i class="fa-regular" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
        </button>

        {{-- Notifications --}}
        <div class="position-relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" class="icon-button admin-notification-trigger btn btn-link p-0 text-decoration-none fs-6 text-secondary position-relative" @click="open = !open" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
                @if($unreadNotifications > 0)
                    <span class="admin-notification-dot">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                @endif
            </button>

            <div x-show="open" x-cloak x-transition.origin.top.right
                class="admin-notification-dropdown position-absolute end-0 mt-2"
                style="z-index: 60;">
                <div class="admin-notification-dropdown__head">
                    <div>
                        <span>Operations alerts</span>
                        <small>{{ $unreadNotifications }} unread</small>
                    </div>
                    @if($unreadNotifications > 0)
                        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                            @csrf
                            <button type="submit">Mark all read</button>
                        </form>
                    @endif
                </div>

                <div class="admin-notification-dropdown__list">
                    @forelse ($headerNotifications as $notification)
                        <a href="{{ $notification->url ?: route('admin.notifications.index') }}"
                            class="admin-notification-mini {{ $notification->isUnread() ? 'is-unread' : '' }}">
                            <span class="admin-notification-mini__icon {{ $notification->tone() }}">
                                <i class="fa-solid {{ $notification->icon() }}"></i>
                            </span>
                            <span class="admin-notification-mini__copy">
                                <strong>{{ $notification->title }}</strong>
                                <small>{{ $notification->created_at?->diffForHumans() }} · {{ $notification->priorityLabel() }}</small>
                            </span>
                        </a>
                    @empty
                        <div class="admin-notification-empty">
                            <i class="fa-regular fa-circle-check"></i>
                            <strong>No active alerts</strong>
                            <span>Critical store events will appear here.</span>
                        </div>
                    @endforelse
                </div>

                <a href="{{ route('admin.notifications.index') }}" class="admin-notification-dropdown__footer">
                    View all notifications
                </a>
            </div>
        </div>

        @auth
            {{-- Profile dropdown --}}
            <div class="position-relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" class="user-menu d-flex align-items-center ps-3 border-0 bg-transparent" @click="open = !open">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold text-uppercase"
                        style="width: 30px; height: 30px; background-color: #233653; font-size: 11px;">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <span class="fw-semibold text-dark ms-2 d-none d-sm-inline" style="font-size: 13px;">{{ Auth::user()->name }}</span>
                    <i class="fa-solid fa-chevron-down ms-2 text-secondary d-none d-sm-inline" style="font-size: 9px;" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="open" x-cloak x-transition.origin.top.right
                    class="position-absolute end-0 mt-2 rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#121c31]"
                    style="width: 250px; z-index: 60;">
                    <div class="d-flex align-items-center gap-2.5 px-3 py-3 border-bottom border-slate-100 dark:border-white/10">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold text-uppercase flex-shrink-0"
                            style="width: 40px; height: 40px; background-color: #233653; font-size: 14px;">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <p class="fw-bold text-slate-900 dark:text-slate-100 mb-0 text-truncate" style="font-size: 13px;">{{ Auth::user()->name }}</p>
                            <p class="text-slate-400 dark:text-slate-500 mb-0 text-truncate" style="font-size: 11.5px;">{{ Auth::user()->email }}</p>
                        </div>
                    </div>

                    <div class="py-1">
                        <a href="{{ route('admin.profile.edit') }}" class="d-flex align-items-center gap-2.5 px-3 py-2 text-decoration-none text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/5" style="font-size: 13px;">
                            <i class="fa-regular fa-user text-secondary" style="width: 16px;"></i> My profile
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="d-flex align-items-center gap-2.5 px-3 py-2 text-decoration-none text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/5" style="font-size: 13px;">
                            <i class="fa-solid fa-gear text-secondary" style="width: 16px;"></i> Settings
                        </a>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="border-top border-slate-100 dark:border-white/10 m-0">
                        @csrf
                        <button type="submit" class="w-100 d-flex align-items-center gap-2.5 px-3 py-2.5 border-0 bg-transparent text-danger fw-semibold hover:bg-red-50 dark:hover:bg-red-500/10" style="font-size: 13px;">
                            <i class="fa-solid fa-right-from-bracket" style="width: 16px;"></i> Sign out
                        </button>
                    </form>
                </div>
            </div>
        @endauth

    </div>
</div>
