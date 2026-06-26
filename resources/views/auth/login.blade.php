<x-guest-layout :bare="true">
    <main class="admin-login">
        <div class="shell">
            <section class="intro">
                <div class="brand"><span class="mark">{{ substr(config('app.name', 'T'), 0, 1) }}</span> {{ strtoupper(config('app.name', 'T-Shirt Shop')) }}</div>
                <div>
                    <div class="eyebrow">Operations console</div>
                    <h1>Good work starts here.</h1>
                    <p>Sign in to manage products, orders, customers, and the operating rhythm of {{ config('app.name', 'the shop') }}.</p>
                </div>
                <div class="meta"><span>Secure workspace</span><span>Phnom Penh · KH</span></div>
            </section>

            <section class="panel">
                <h2>Admin sign in</h2>
                <p>Use an account with an Admin or Super Admin role.</p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <label>
                        Email address
                        <input type="email" name="email" value="{{ old('email') }}"
                            autocomplete="email" required autofocus>
                        <x-input-error :messages="$errors->get('email')" class="field-error" />
                    </label>

                    <label>
                        Password
                        <input type="password" name="password" autocomplete="current-password" required>
                        <x-input-error :messages="$errors->get('password')" class="field-error" />
                    </label>

                    <div class="row-between">
                        <label class="remember">
                            <input type="checkbox" name="remember" value="1"> {{ __('Keep me signed in') }}
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                        @endif
                    </div>

                    <button type="submit">{{ __('Sign in to admin') }}</button>
                </form>

                <div class="secure">Protected by role-based access control</div>
            </section>
        </div>
    </main>
</x-guest-layout>
