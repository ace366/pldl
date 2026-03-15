<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full"
                          type="email" name="email" :value="old('email')"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                          type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        {{-- パスワード再発行 --}}
        @if (Route::has('password.request'))
            <div class="mt-4 text-right">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md
                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                   href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            </div>
        @endif

        {{-- ボタン（横並び固定） --}}
        <div class="mt-6 flex gap-4">
            {{-- ログイン（submit） --}}
            <button type="submit"
                    class="group w-1/2 inline-flex flex-col items-center gap-2 focus:outline-none">
                <span class="inline-flex items-center justify-center">
                    <img src="{{ asset('images/login.png') }}"
                        alt="ログイン"
                        class="h-14 w-auto object-contain transition-transform duration-150 group-hover:-translate-y-0.5">
                </span>
                <span class="text-sm font-semibold text-gray-900">ログイン</span>
            </button>

            {{-- 新規登録（リンク） --}}
            @if (Route::has('register.lock'))
                <a href="{{ route('register.lock', [], false) }}"
                class="group w-1/2 inline-flex flex-col items-center gap-2 focus:outline-none">
                    <span class="inline-flex items-center justify-center">
                        <img src="{{ asset('images/new.png') }}"
                            alt="新規登録"
                            class="h-14 w-auto object-contain transition-transform duration-150 group-hover:-translate-y-0.5">
                    </span>
                    <span class="text-sm font-semibold text-gray-900">新規登録</span>
                </a>
            @endif
        </div>

        <p class="mt-4 text-xs text-gray-500">
            新規登録は関係者向けです。事前共有パスワードの入力後に登録画面へ進みます。
        </p>

    </form>
</x-guest-layout>
