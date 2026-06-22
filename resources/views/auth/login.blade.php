<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">

            <div class="d-flex justify-content-center mb-3 fs-3 text-gray-900">
                {{ __('Login') }}
            </div>
        </div>
        <div class="mb-4">
            <div class="d-flex align-items-center position-relative bg-white p-2 rounded-3 shadow-sm" style="border-left: 5px solid #111827; border-top: 1px solid #e9ecef; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">

                <div class="px-3 text-secondary">
                    <i class="fa-regular fa-envelope fs-5"></i>
                </div>

                <div class="text-muted opacity-50" style="font-size: 20px; font-weight: 300;">|</div>

                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Input your Email" class="form-control border-0 bg-transparent py-2 shadow-none" style="font-style: italic; color: #6c757d;"
                    required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mb-4">
            <div class="d-flex align-items-center position-relative bg-white p-2 rounded-3 shadow-sm" style="border-left: 5px solid #111827; border-top: 1px solid #e9ecef; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">

                <div class="px-3 text-secondary">
                    <i class="fa-solid fa-key fs-5"></i>
                </div>

                <div class="text-muted opacity-50" style="font-size: 20px; font-weight: 300;">|</div>

                <input id="password" type="password" name="password" placeholder="Input your password" class="form-control border-0 bg-transparent py-2 shadow-none" style="font-style: italic; color: #6c757d;"
                    required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="d-flex justify-content-between align-items-center mt-4">

            <label for="remember_me" class="inline-flex items-center mb-0">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-gray-900 shadow-sm focus:text-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
            <a class="underline text-sm text-gray-600 hover:text-gray-900 " href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
            @endif

        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 " href="{{ route('register') }}">
                {{ __('Register') }}
            </a>

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>