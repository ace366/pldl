<x-app-layout>
    @php
        $channelLabels = $channelLabels ?? [
            'tel' => '電話',
            'meeting' => '面談',
            'mail' => 'メール',
            'other' => 'その他',
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            {{-- 戻る --}}
            <div class="mb-4 flex items-center justify-between gap-3">
                <a href="{{ route('admin.children.index') }}"
                   class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900 underline">
                    ← 児童一覧へ戻る
                </a>

                <a href="{{ route('admin.children.edit', $child) }}"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-lg
                          bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm">
                    児童情報を編集
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-800 text-sm">
                    <div class="font-semibold mb-1">入力内容をご確認ください</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ✅ 折りたたみ：上部固定（児童＋保護者） --}}
            @php
                $gs = $child->guardians ?? collect();
                $siblings = $siblings ?? collect();
            @endphp

            <div
                x-data="{ open: false }"
                class="sticky top-0 z-20"
            >
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

                    {{-- ヘッダー（常時表示） --}}
                    <div class="px-4 sm:px-5 py-3 border-b bg-white">
                        <div class="flex items-center justify-between gap-3">

                            {{-- 左：コンパクト情報 --}}
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <div class="text-sm sm:text-base font-semibold text-gray-900 truncate">
                                        TEL票：{{ $child->last_name }} {{ $child->first_name }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">
                                        ID: {{ $child->child_code ?? '—' }}
                                    </div>

                                    @if($child->status === 'enrolled')
                                        <span class="inline-flex px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">
                                            在籍
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">
                                            退会
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                        <span class="hidden sm:inline">保護者</span>
                                        <span class="font-semibold text-gray-700">{{ $gs->count() }}</span>
                                        <span class="hidden sm:inline">名</span>
                                    </span>

                                    @if($siblings->isNotEmpty())
                                        <span class="inline-flex items-center gap-1 text-xs text-emerald-700">
                                            <span class="hidden sm:inline">きょうだい</span>
                                            <span class="font-semibold">{{ $siblings->count() }}</span>
                                            <span class="hidden sm:inline">名</span>
                                        </span>
                                    @endif
                                </div>

                                @if($siblings->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap items-start gap-2">
                                        @foreach($siblings as $s)
                                            @php
                                                $sName = trim((string)$s->last_name.' '.(string)$s->first_name);
                                                $sKana = trim((string)$s->last_name_kana.' '.(string)$s->first_name_kana);
                                            @endphp
                                            <a href="{{ route('admin.children.tel.index', $s) }}"
                                               class="inline-flex flex-col rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 hover:bg-emerald-100">
                                                <span class="text-[10px] leading-none text-gray-600">
                                                    {{ $sKana !== '' ? $sKana : 'ふりがな未設定' }}
                                                </span>
                                                <span class="mt-1 text-xs font-semibold leading-none text-emerald-800">
                                                    {{ $sName }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- 閉じている時だけ、追加で1行だけ出す（省スペースだけど情報は落とさない） --}}
                                <div x-show="!open" x-cloak class="mt-1 text-xs text-gray-600">
                                    学年：<span class="font-semibold">{{ $child->grade }}年</span>
                                    <span class="mx-2 text-gray-300">|</span>
                                    拠点：<span class="font-semibold">{{ $child->baseMaster?->name ?? '—' }}</span>
                                    <span class="mx-2 text-gray-300">|</span>
                                    学校：<span class="font-semibold">{{ $child->school?->name ?? '—' }}</span>
                                </div>
                            </div>

                            {{-- 右：トグルボタン --}}
                            <div class="shrink-0 flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                           bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold"
                                    :aria-expanded="open.toString()"
                                    aria-controls="child-guardian-panel"
                                >
                                    <span x-text="open ? '▲ 隠す' : '▽ 表示'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 本体（開いている時だけ表示） --}}
                    <div id="child-guardian-panel" x-show="open" x-cloak>
                        <div class="p-4 sm:p-5">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                                {{-- 左：児童情報 --}}
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <div class="text-base sm:text-lg font-semibold text-gray-900">
                                            {{ $child->last_name }} {{ $child->first_name }}
                                        </div>
                                        <div class="text-sm text-gray-500 font-mono">
                                            ID: {{ $child->child_code ?? '—' }}
                                        </div>

                                        @if($child->status === 'enrolled')
                                            <span class="inline-flex px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">
                                                在籍
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">
                                                退会
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700">
                                        <div class="rounded-md bg-gray-50 p-2">
                                            学年：<span class="font-semibold">{{ $child->grade }}年</span>
                                        </div>
                                        <div class="rounded-md bg-gray-50 p-2">
                                            拠点：<span class="font-semibold">{{ $child->baseMaster?->name ?? '—' }}</span>
                                        </div>
                                        <div class="rounded-md bg-gray-50 p-2 sm:col-span-2">
                                            学校：<span class="font-semibold">{{ $child->school?->name ?? '—' }}</span>
                                        </div>
                                    </div>

                                    @if(!empty($child->note))
                                        <div class="mt-3 text-sm">
                                            <div class="text-xs font-semibold text-gray-600 mb-1">メモ</div>
                                            <div class="rounded-md border border-gray-200 bg-white p-2 text-gray-800 whitespace-pre-wrap">
                                                {{ $child->note }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- 右：保護者情報 --}}
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold text-gray-800">
                                            保護者情報
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $gs->count() }}名
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-3">
                                        @if($gs->isEmpty())
                                            <div class="text-sm text-gray-500">保護者が未登録です。</div>
                                        @else
                                            @foreach($gs as $g)
                                                <div class="rounded-lg border border-gray-200 p-3">
                                                    <div class="font-semibold text-gray-900 flex flex-wrap items-center gap-2">
                                                        <span>{{ $g->last_name }} {{ $g->first_name }}</span>
                                                        @if(!empty($g->pivot?->relationship))
                                                            <span class="text-xs text-gray-600">（{{ $g->pivot->relationship }}）</span>
                                                        @endif
                                                    </div>

                                                    <div class="mt-1 text-sm text-gray-700 space-y-1">
                                                        <div>メール：<span class="font-mono">{{ $g->email ?? '—' }}</span></div>
                                                        <div>電話：<span class="font-mono">{{ $g->phone ?? '—' }}</span></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ✅ 履歴（最新→過去） --}}
            <div class="mt-4 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b bg-gray-50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-800">
                            履歴（最新 → 過去）
                        </div>
                        <div class="text-xs text-gray-500">
                            10件以上はページで過去へ
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-6 space-y-4">
                    @forelse($logs as $log)
                        @php
                            $ch = (string)($log->channel ?? 'tel');
                            $label = $channelLabels[$ch] ?? $ch;

                            $badgeClass = match ($ch) {
                                'tel'     => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'meeting' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'mail'    => 'bg-sky-50 text-sky-800 border-sky-200',
                                default   => 'bg-gray-50 text-gray-700 border-gray-200',
                            };

                            $creatorName = null;
                            if ($log->creator) {
                                $creatorName = trim(($log->creator->name ?? ''));
                                if ($creatorName === '') $creatorName = '（名前未設定）';
                            }
                        @endphp

                        <div class="rounded-xl border border-gray-200 px-3 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-semibold {{ $badgeClass }}">
                                            {{ $label }}
                                        </span>

                                        <div class="font-semibold text-gray-900 truncate">
                                            {{ $log->title }}
                                        </div>
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500">
                                        入力者：
                                        <span class="font-semibold text-gray-700">
                                            {{ $creatorName ?? '—' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="shrink-0 text-xs text-gray-500 font-mono">
                                    {{ optional($log->created_at)->format('Y-m-d H:i') }}
                                </div>
                            </div>

                            @php
                                $bodyText = trim((string)$log->body);
                            @endphp
                            <div class="mt-1 text-sm text-gray-800 whitespace-pre-wrap text-left">{{ $bodyText }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">
                            まだTEL票がありません。下のフォームから最初の記録を追加してください。
                        </div>
                    @endforelse

                    <div class="pt-2 flex items-center justify-between text-sm">
                        <div class="text-gray-600">
                            @if(method_exists($logs, 'firstItem') && $logs->total() > 0)
                                {{ $logs->firstItem() }}〜{{ $logs->lastItem() }} / {{ $logs->total() }}件
                            @endif
                        </div>
                        <div>
                            {{ $logs->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ✅ 画面一番下：入力フォーム（種別追加） --}}
            <div class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b bg-gray-50">
                    <div class="text-sm font-semibold text-gray-800">TEL票を追加</div>
                </div>

                <form method="POST" action="{{ route('admin.children.tel.store', $child) }}" class="p-4 sm:p-6 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                種別 <span class="text-rose-600">*</span>
                            </label>
                            <select name="channel"
                                    required
                                    class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @foreach($channelLabels as $key => $lab)
                                    <option value="{{ $key }}" @selected(old('channel', 'tel') === $key)>{{ $lab }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                タイトル <span class="text-rose-600">*</span>
                            </label>
                            <input type="text"
                                   name="title"
                                   value="{{ old('title') }}"
                                   required
                                   maxlength="120"
                                   class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="例：欠席連絡、迎え時間変更、体調相談 など">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            内容 <span class="text-rose-600">*</span>
                        </label>
                        <textarea name="body"
                                  rows="5"
                                  required
                                  maxlength="5000"
                                  class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                  placeholder="やり取り内容を記録します（電話内容／伝達事項／確認事項など）">{{ old('body') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-5 py-2 rounded-lg
                                       bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm">
                            追加する
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    {{-- Alpine用：x-cloak（開閉チラつき防止） --}}
    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
