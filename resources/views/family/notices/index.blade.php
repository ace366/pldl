<x-app-layout>
    <div class="py-10 bg-gradient-to-br from-pink-50 via-yellow-50 to-blue-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-4">

            {{-- 🌈 ヒーロー --}}
            <div class="mb-8 text-center">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-indigo-600 tracking-wide">
                    🌟 きょうのお知らせ 🌟
                </h1>
                <p class="mt-2 text-gray-600 text-sm sm:text-base">
                    たいせつなお知らせをチェックしよう！
                </p>
            </div>

            {{-- 📢 お知らせカード --}}
            @forelse (($notices ?? collect()) as $notice)
                <div class="mb-6 bg-white rounded-3xl shadow-lg border-4 border-yellow-200
                            hover:scale-[1.01] transition-transform duration-200">

                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">📣</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">
                                {{ $notice->title }}
                            </h2>
                        </div>

                        <div class="notice-rich-body text-gray-700 text-base sm:text-lg leading-relaxed">
                            {!! $notice->body !!}
                        </div>

                        @php
                            // published_at / created_at どちらでも表示できるようにフォールバック
                            $date = $notice->published_at ?? $notice->created_at ?? null;
                        @endphp

                        @if($date)
                            <div class="mt-4 text-right text-xs text-gray-500">
                                {{ \Illuminate\Support\Carbon::parse($date)->format('Y年n月j日') }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl shadow p-8 text-center text-gray-500">
                    いまは お知らせは ありません 🌱
                </div>
            @endforelse

            {{-- ✏️ 管理者のみ（家族ログイン時は通常表示されません） --}}
            @if(auth()->check() && auth()->user()?->role === 'admin')
                <div class="mt-10 text-center">
                    @if(\Illuminate\Support\Facades\Route::has('admin.notices.edit'))
                        <a href="{{ route('admin.notices.edit') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-full
                                  bg-indigo-600 text-black font-semibold shadow
                                  hover:bg-indigo-700 transition">
                            ✏️ お知らせを編集する
                        </a>
                    @endif
                </div>
            @endif

        </div>
    </div>

    @once
        <style>
            .notice-rich-body p { margin: 0.4rem 0; }
            .notice-rich-body ol { margin: 0.4rem 0; padding-left: 1.4rem; list-style: decimal; }
            .notice-rich-body ul { margin: 0.4rem 0; padding-left: 0; list-style: none; }
            .notice-rich-body ul li { position: relative; padding-left: 1.2rem; }
            .notice-rich-body ul li::before { content: "・"; position: absolute; left: 0; }
            .notice-rich-body a { color: #4f46e5; text-decoration: underline; }
            .notice-rich-body img {
                display: block;
                max-width: 100%;
                width: 100%;
                height: auto;
                border-radius: 0.75rem;
                margin: 0.6rem 0;
                object-fit: contain;
            }
            .notice-rich-body iframe {
                display: block;
                width: 100%;
                max-width: 100%;
                aspect-ratio: 16 / 9;
                height: auto;
                border: 0;
                border-radius: 0.75rem;
                margin: 0.6rem 0;
                background: #000;
            }
            .notice-rich-body video {
                display: block;
                width: 100%;
                max-width: 100%;
                height: auto;
                border-radius: 0.75rem;
                margin: 0.6rem 0;
                background: #000;
            }
        </style>
    @endonce
</x-app-layout>
