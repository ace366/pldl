<x-guest-layout>
    <div class="max-w-md mx-auto">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-lg font-semibold text-gray-800 mb-2">ご家庭用ログイン</h1>
            <p class="text-sm text-gray-600 mb-4">
                お子さまの「4桁ID」でログインしてください。
            </p>

            <form method="POST" action="{{ route('family.login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="child_code" class="block text-sm font-medium text-gray-700">
                        児童ID（4桁）
                    </label>
                    <input
                        id="child_code"
                        name="child_code"
                        inputmode="numeric"
                        pattern="\d{4}"
                        maxlength="4"
                        value="{{ old('child_code') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="例：1234"
                        required
                        autofocus
                    >
                    @error('child_code')
                        <p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ボタン：横並び --}}
                <div class="flex items-end justify-between gap-4 pt-2">
                    {{-- ログイン（login.png） --}}
                    <button type="submit" class="group inline-flex flex-col items-center gap-1">
                        <span class="inline-flex items-center justify-center w-14 h-14 rounded-full
                                     bg-slate-50 border border-slate-200 shadow-sm
                                     transition-all duration-200
                                     group-hover:bg-slate-100 group-hover:-translate-y-0.5 group-hover:shadow
                                     focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                            <img src="{{ asset('images/login.png') }}" alt="ログイン" class="w-8 h-8 object-contain">
                        </span>
                        <span class="text-sm font-semibold text-gray-900">ログイン</span>
                    </button>

                    {{-- 新規登録（new.png） --}}
                    <a href="{{ route('enroll.create') }}"
                       class="group inline-flex flex-col items-center gap-1">
                        <span class="inline-flex items-center justify-center w-14 h-14 rounded-full
                                     bg-slate-50 border border-slate-200 shadow-sm
                                     transition-all duration-200
                                     group-hover:bg-slate-100 group-hover:-translate-y-0.5 group-hover:shadow
                                     focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                            <img src="{{ asset('images/new.png') }}" alt="新規登録" class="w-8 h-8 object-contain">
                        </span>
                        <span class="text-sm font-semibold text-gray-900">新規登録</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
