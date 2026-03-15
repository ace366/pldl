<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    @php
        $holderKanaDisplay = old('bank_account_holder_kana');
        if (!$holderKanaDisplay) {
            $holderKanaDisplay = $user->bank_account_holder_kana;
        }
        if (!$holderKanaDisplay) {
            $rawKana = trim(($user->last_name_kana ?? '') . ($user->first_name_kana ?? ''));
            if ($rawKana === '') {
                $rawKana = (string)($user->name ?? '');
            }
            $rawKana = preg_replace('/\\s+/u', '', $rawKana);
            $holderKanaDisplay = $rawKana !== '' ? mb_convert_kana($rawKana, 'KV', 'UTF-8') : '';
        }
    @endphp

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" value="電話番号" />
            <x-text-input
                id="phone"
                name="phone"
                type="tel"
                class="mt-1 block w-full"
                :value="old('phone', $user->phone)"
                inputmode="numeric"
                autocomplete="tel"
                placeholder="09012345678"
            />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div id="profile-bank-lookup" class="border rounded-lg p-4 space-y-4">
            <div class="text-sm font-semibold text-gray-700">給与の振込口座</div>

            <div>
                <x-input-label for="bank_name" value="銀行名" />
                <x-text-input
                    id="bank_name"
                    name="bank_name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('bank_name', $user->bank_name)"
                    placeholder="銀行名または銀行コードで検索"
                    autocomplete="off"
                />
                <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
                <div id="bank-search-results" class="mt-2 hidden rounded-md border bg-white text-sm shadow"></div>
            </div>

            <div>
                <x-input-label for="bank_code" value="銀行No（4桁）" />
                <x-text-input
                    id="bank_code"
                    name="bank_code"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('bank_code', $user->bank_code)"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="0001"
                />
                <x-input-error class="mt-2" :messages="$errors->get('bank_code')" />
            </div>

            <div>
                <x-input-label for="bank_branch_name" value="支店名" />
                <x-text-input
                    id="bank_branch_name"
                    name="bank_branch_name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('bank_branch_name', $user->bank_branch_name)"
                    placeholder="支店名または支店コードで検索"
                    autocomplete="off"
                />
                <x-input-error class="mt-2" :messages="$errors->get('bank_branch_name')" />
                <div id="branch-search-results" class="mt-2 hidden rounded-md border bg-white text-sm shadow"></div>
            </div>

            <div>
                <x-input-label for="bank_branch_code" value="支店No（3桁）" />
                <x-text-input
                    id="bank_branch_code"
                    name="bank_branch_code"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('bank_branch_code', $user->bank_branch_code)"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="001"
                />
                <x-input-error class="mt-2" :messages="$errors->get('bank_branch_code')" />
            </div>

            <div>
                <x-input-label for="bank_account_type" value="種別" />
                <select id="bank_account_type" name="bank_account_type" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">選択してください</option>
                    <option value="ordinary" @selected(old('bank_account_type', $user->bank_account_type) === 'ordinary')>普通</option>
                    <option value="current" @selected(old('bank_account_type', $user->bank_account_type) === 'current')>当座</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('bank_account_type')" />
            </div>

            <div>
                <x-input-label for="bank_account_number" value="口座No" />
                <x-text-input
                    id="bank_account_number"
                    name="bank_account_number"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('bank_account_number', $user->bank_account_number)"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="1234567"
                />
                <x-input-error class="mt-2" :messages="$errors->get('bank_account_number')" />
            </div>

            <div>
                <x-input-label for="bank_account_holder_kana" value="口座名義人（本人）" />
                <x-text-input
                    id="bank_account_holder_kana"
                    name="bank_account_holder_kana"
                    type="text"
                    class="mt-1 block w-full bg-gray-50"
                    :value="$holderKanaDisplay"
                    readonly
                />
                <p class="mt-1 text-xs text-gray-500">ふりがな（かな）から自動生成されます。</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
