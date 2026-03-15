<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h1 class="text-xl font-semibold text-gray-800 mb-4">登録が完了しました</h1>

                <div class="rounded-xl border p-4 bg-gray-50">
                    <div class="text-sm font-semibold text-gray-800 mb-2">登録内容</div>

                    <div class="text-sm text-gray-700">
                        <div>児童：<span class="font-semibold">{{ $child->full_name }}</span>（ID: {{ $child->child_code }}）</div>
                        <div>学年：{{ $child->grade }}年 / 学校：{{ $child->school?->name ?? '—' }}</div>
                        <div class="mt-2">保護者：<span class="font-semibold">{{ $guardian->name ?? ($guardian->last_name.' '.$guardian->first_name) }}</span></div>
                        <div>連絡：{{ $guardian->phone ?? '—' }} / {{ $guardian->email ?? '—' }}</div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">保護者用の確認ページURL</div>

                    @if($confirmUrl)
                        <div class="flex flex-col gap-3">
                            <input type="text" readonly id="confirmUrl"
                                   value="{{ $confirmUrl }}"
                                   class="w-full rounded-md border-gray-300 text-sm">

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ $confirmUrl }}" target="_blank"
                                   class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                                    確認ページを開く（保護者表示）
                                </a>

                                <button type="button" id="copyBtn"
                                        class="inline-flex items-center px-4 py-2 rounded-md bg-slate-700 text-white text-sm hover:bg-slate-800">
                                    URLをコピー
                                </button>

                                <a href="{{ route('admin.children.edit', $child) }}"
                                   class="inline-flex items-center px-4 py-2 rounded-md bg-white border text-sm hover:bg-gray-50">
                                    児童編集へ
                                </a>
                            </div>

                            <p class="text-xs text-gray-500">
                                ※このURLは署名付きで安全です（期限：7日）。必要なら期限延長もできます。
                            </p>
                        </div>
                    @else
                        <div class="text-sm text-rose-700">確認URLが生成できませんでした。</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        const btn = document.getElementById('copyBtn');
        const input = document.getElementById('confirmUrl');
        if (btn && input) {
            btn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(input.value);
                    btn.textContent = 'コピーしました';
                    setTimeout(() => btn.textContent = 'URLをコピー', 1500);
                } catch (e) {
                    input.select();
                    document.execCommand('copy');
                }
            });
        }
    </script>
</x-app-layout>
