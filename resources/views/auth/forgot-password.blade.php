<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
         <div class="mb-4">
            <div class="d-flex align-items-center position-relative bg-white p-2 rounded-3 shadow-sm" style="border-left: 5px solid #111827; border-top: 1px solid #e9ecef; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">

                <div class="px-3 text-secondary">
                    <i class="fa-regular fa-envelope fs-5"></i>
                </div>

                <div class="text-muted opacity-50" style="font-size: 20px; font-weight: 300;">|</div>

                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Input your Email" class="form-control border-0 bg-transparent py-2 shadow-none" style="font-style: italic; color: #6c757d;"
                    required autofocus />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
