<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf


        <div class="mb-3">

            <div class="d-flex justify-content-center mb-3 fs-3 text-gray-900">
                {{ __('Register') }}
            </div>
        </div>

        <!-- Name -->
        <div class="mb-4">
            <div class="d-flex align-items-center position-relative bg-white p-2 rounded-3 shadow-sm" style="border-left: 5px solid #111827; border-top: 1px solid #e9ecef; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">

                <div class="px-3 text-secondary">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="text-muted opacity-50" style="font-size: 20px; font-weight: 300;">|</div>

                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Input your Name" class="form-control border-0 bg-transparent py-2 shadow-none" style="font-style: italic; color: #6c757d;"
                    required autofocus autocomplete="name" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>


        <!-- Email Address -->
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
        
        <!-- Phone -->
        <!-- <div class="mb-4">
            <div class="d-flex align-items-center position-relative bg-white p-2 rounded-3 shadow-sm" style="border-left: 5px solid #111827; border-top: 1px solid #e9ecef; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">

                <div class="px-3 text-secondary">
                    <i class="fa-solid fa-phone fs-5"></i>
                </div>

                <div class="text-muted opacity-50" style="font-size: 20px; font-weight: 300;">|</div>

                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="Input your Phone" class="form-control border-0 bg-transparent py-2 shadow-none" style="font-style: italic; color: #6c757d;"
                    required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div> -->

        <!-- Password -->
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

        <!-- Confirm Password -->
        <div class="mb-4">
            <div class="d-flex align-items-center position-relative bg-white p-2 rounded-3 shadow-sm" style="border-left: 5px solid #111827; border-top: 1px solid #e9ecef; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">

                <div class="px-3 text-secondary">
                    <i class="fa-solid fa-key fs-5"></i>
                </div>

                <div class="text-muted opacity-50" style="font-size: 20px; font-weight: 300;">|</div>

                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm your password" class="form-control border-0 bg-transparent py-2 shadow-none" style="font-style: italic; color: #6c757d;"
                    required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-5">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 " href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>