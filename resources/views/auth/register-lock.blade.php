<x-guest-layout>
    <div class="space-y-6">
        <div class="space-y-2">
            <h1 class="text-lg font-semibold text-gray-900">新規登録の事前確認</h1>
            <p class="text-sm text-gray-600">
                新規登録は、案内を受けた方のみ利用できます。事前共有パスワードを入力して進んでください。
            </p>
        </div>

        @if (session('error'))
            <div class="rounded-md bg-rose-50 p-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.lock.verify', [], false) }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="gate_password" value="事前共有パスワード" />
                <div class="relative mt-1">
                    <x-text-input id="gate_password"
                                  name="gate_password"
                                  type="password"
                                  class="block w-full pr-12"
                                  required
                                  autofocus />
                    <button type="button"
                            id="toggle-gate-password"
                            class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-500 hover:text-gray-700"
                            aria-label="パスワードを表示">
                        <svg id="gate-eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg id="gate-eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0013.414 13.4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.092A9.944 9.944 0 0112 5c4.478 0 8.269 2.943 9.543 7a10.023 10.023 0 01-4.132 5.411M6.228 6.228A9.956 9.956 0 002.457 12a10.025 10.025 0 003.305 4.627" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('gate_password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('login', [], false) }}" class="text-sm text-gray-600 underline hover:text-gray-900">
                    ログインへ戻る
                </a>
                <x-primary-button>
                    確認して進む
                </x-primary-button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const input = document.getElementById('gate_password');
            const toggle = document.getElementById('toggle-gate-password');
            const openIcon = document.getElementById('gate-eye-open');
            const closedIcon = document.getElementById('gate-eye-closed');

            if (!input || !toggle || !openIcon || !closedIcon) {
                return;
            }

            toggle.addEventListener('click', () => {
                const hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                openIcon.classList.toggle('hidden', !hidden);
                closedIcon.classList.toggle('hidden', hidden);
            });
        })();
    </script>
</x-guest-layout>
