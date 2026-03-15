{{-- resources/views/enroll/complete.blade.php --}}
<x-guest-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <div class="rounded-3xl border border-slate-200 bg-white shadow-[0_18px_50px_-30px_rgba(15,23,42,0.6)]">
                <div class="px-6 py-8 sm:px-8 sm:py-10">
                    <div id="react-enroll-complete"></div>

                    <script id="enroll-complete-props" type="application/json">
                        {!! json_encode([
                            'loginId' => $loginId,
                            'lineUrl' => 'https://lin.ee/tmOA7d8',
                            'lineImg' => 'https://scdn.line-apps.com/n/line_add_friends/btn/ja.png',
                        ], JSON_UNESCAPED_UNICODE) !!}
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
