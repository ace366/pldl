@php
    $year = (int)($year ?? now()->format('Y'));
    $stats = $stats ?? collect();
    $years = $years ?? [];
    $ntaTopUrl = $ntaTopUrl ?? 'https://www.nta.go.jp/users/gensen/index.htm';
    $ntaYearUrl = $ntaYearUrl ?? '';
    $defaultCsvPath = $defaultCsvPath ?? '';
    $mappingToken = $mappingToken ?? '';
    $mappingSheets = $mappingSheets ?? [];
    $mappingSourceName = $mappingSourceName ?? '';

    $keywordMatch = static function (array $columns, array $keywords): ?int {
        foreach ($columns as $c) {
            $text = mb_strtolower((string)($c['label'] ?? ''));
            foreach ($keywords as $k) {
                if ($k !== '' && str_contains($text, mb_strtolower($k))) {
                    return (int)$c['index'];
                }
            }
        }
        return null;
    };

    $optionText = static function (array $c): string {
        $letter = (string)($c['letter'] ?? '');
        $label = trim((string)($c['label'] ?? ''));
        $sample = trim((string)($c['sample'] ?? ''));
        $text = '['.$letter.'] '.($label !== '' ? $label : '（見出し不明）');
        if ($sample !== '') {
            $text .= ' / 例: '.\Illuminate\Support\Str::limit($sample, 30);
        }
        return $text;
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">源泉税テーブル取込</h2>
            <a href="{{ route('admin.payroll.index', ['month' => now()->format('Y-m')]) }}"
               class="text-sm text-gray-600 underline hover:text-gray-900">給与一覧へ戻る</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-3">
                <div class="text-sm text-gray-700">
                    国税庁リンク（毎年更新）:
                    <a href="{{ $ntaTopUrl }}" target="_blank" rel="noopener noreferrer" class="underline text-indigo-700">源泉徴収義務者向けトップ</a>
                    /
                    <a href="{{ $ntaYearUrl }}" target="_blank" rel="noopener noreferrer" class="underline text-indigo-700">{{ $year }}年ページ</a>
                </div>
                <div class="text-xs text-gray-500">
                    既定CSV保存先: <code>{{ $defaultCsvPath }}</code>
                </div>
                <div class="text-xs text-gray-500">
                    2026年の国税庁公式 <code>01-07.xls</code> はURL指定・ファイル指定のどちらでもそのまま取り込めます。
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="font-semibold text-gray-800 mb-3">取込済み状況（{{ $year }}年）</div>
                @if($stats->isEmpty())
                    <div class="text-sm text-gray-500">まだ取り込みデータがありません。</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left px-3 py-2">pay_type</th>
                                <th class="text-left px-3 py-2">column_type</th>
                                <th class="text-right px-3 py-2">行数</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($stats as $s)
                                <tr>
                                    <td class="px-3 py-2">{{ $s->pay_type }}</td>
                                    <td class="px-3 py-2">{{ $s->column_type }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((int)$s->cnt) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="font-semibold text-gray-800 mb-2">1) ファイルを選んで取込準備</div>
                    <form method="POST" action="{{ route('admin.payroll.withholding.import') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="hidden" name="mode" value="file">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">対象年</label>
                            <input type="number" name="year" min="2000" max="2100" value="{{ old('year', $year) }}"
                                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">ファイル（csv / xlsx / xls）</label>
                            <input type="file" name="tax_file" accept=".csv,.xlsx,.xls"
                                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <p class="mt-1 text-xs text-gray-500">2026年の国税庁公式 <code>01-07.xls</code> に対応しています。</p>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 h-10 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            次へ（ヘッダ確認）
                        </button>
                    </form>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="font-semibold text-gray-800 mb-2">2) URL指定で取込準備</div>
                    <form method="POST" action="{{ route('admin.payroll.withholding.import') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="mode" value="url">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">対象年</label>
                            <input type="number" name="year" min="2000" max="2100" value="{{ old('year', $year) }}"
                                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">ファイルURL</label>
                            <input type="url" name="source_url" value="{{ old('source_url', '') }}"
                                   placeholder="https://.../zeigakuhyo2026.xlsx"
                                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <p class="mt-1 text-xs text-gray-500">例: <code>https://www.nta.go.jp/publication/pamph/gensen/zeigakuhyo2026/data/01-07.xls</code></p>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 h-10 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            次へ（ヘッダ確認）
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="font-semibold text-gray-800 mb-2">3) 最新ページから自動検出して取込準備</div>
                <p class="text-sm text-gray-600 mb-3">
                    {{ $year }}年ページ（{{ $ntaYearUrl }}）から Excel/CSV リンクを自動検出します。
                </p>
                <form method="POST" action="{{ route('admin.payroll.withholding.import') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    @csrf
                    <input type="hidden" name="mode" value="auto">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">対象年</label>
                        <input type="number" name="year" min="2000" max="2100" value="{{ old('year', $year) }}"
                               class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 h-10 rounded-md bg-slate-800 text-white text-sm font-semibold hover:bg-slate-900">
                        次へ（ヘッダ確認）
                    </button>
                </form>
            </div>

            @if($mappingToken !== '' && !empty($mappingSheets))
                <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-4">
                    <div class="font-semibold text-gray-800">4) ヘッダマッピングして反映実行</div>
                    <div class="text-sm text-gray-600">
                        取込元: <span class="font-semibold">{{ $mappingSourceName }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        画像のような税額表（「以上」「未満」「甲 0〜7人」「乙」）では、列を指定して取り込みます。
                    </div>

                    @foreach($mappingSheets as $sheet)
                        @php
                            $columns = $sheet['columns'] ?? [];
                            $sheetIndex = (int)($sheet['sheet_index'] ?? 0);
                            $sheetName = (string)($sheet['sheet_name'] ?? ('Sheet'.($sheetIndex + 1)));
                            $prefix = 's'.$sheetIndex.'_';

                            $minGuess = $keywordMatch($columns, ['以上', '下限', '開始', '最小']);
                            $maxGuess = $keywordMatch($columns, ['未満', '上限', '終了', '最大']);
                            $otsuGuess = $keywordMatch($columns, ['乙']);
                            $kouGuess = [
                                0 => $keywordMatch($columns, ['0人', '0 人']),
                                1 => $keywordMatch($columns, ['1人', '1 人']),
                                2 => $keywordMatch($columns, ['2人', '2 人']),
                                3 => $keywordMatch($columns, ['3人', '3 人']),
                                4 => $keywordMatch($columns, ['4人', '4 人']),
                                5 => $keywordMatch($columns, ['5人', '5 人']),
                                6 => $keywordMatch($columns, ['6人', '6 人']),
                                7 => $keywordMatch($columns, ['7人', '7 人']),
                            ];
                        @endphp

                        <form method="POST" action="{{ route('admin.payroll.withholding.import_mapped') }}" class="border border-slate-200 rounded-xl p-4 space-y-3">
                            @csrf
                            <input type="hidden" name="year" value="{{ old('year', $year) }}">
                            <input type="hidden" name="source_token" value="{{ $mappingToken }}">
                            <input type="hidden" name="sheet_index" value="{{ $sheetIndex }}">

                            <div class="font-semibold text-slate-800">{{ $sheetName }}</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">pay_type</label>
                                    <select name="pay_type" class="w-full rounded-md border-gray-300 text-sm">
                                        <option value="monthly" @selected(old('pay_type', 'monthly') === 'monthly')>monthly（月額表）</option>
                                        <option value="daily" @selected(old('pay_type') === 'daily')>daily（日額表）</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">乙欄税額列（otsu）</label>
                                    <select name="otsu_col" class="w-full rounded-md border-gray-300 text-sm">
                                        @foreach($columns as $c)
                                            <option value="{{ $c['index'] }}"
                                                    @selected((string)old('otsu_col', $otsuGuess) === (string)$c['index'])>
                                                {{ $optionText($c) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">金額下限列（min_amount）</label>
                                    <select name="min_col" class="w-full rounded-md border-gray-300 text-sm">
                                        @foreach($columns as $c)
                                            <option value="{{ $c['index'] }}"
                                                    @selected((string)old('min_col', $minGuess) === (string)$c['index'])>
                                                {{ $optionText($c) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">金額上限列（max_amount）</label>
                                    <select name="max_col" class="w-full rounded-md border-gray-300 text-sm">
                                        @foreach($columns as $c)
                                            <option value="{{ $c['index'] }}"
                                                    @selected((string)old('max_col', $maxGuess) === (string)$c['index'])>
                                                {{ $optionText($c) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="text-xs font-semibold text-gray-700 mt-2">甲欄税額列（kou / dep_count）</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @for($dep = 0; $dep <= 7; $dep++)
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">甲 {{ $dep }} 人（dep_count={{ $dep }}）</label>
                                        <select name="kou_col_{{ $dep }}" class="w-full rounded-md border-gray-300 text-sm">
                                            <option value="">未使用</option>
                                            @foreach($columns as $c)
                                                <option value="{{ $c['index'] }}"
                                                        @selected((string)old('kou_col_'.$dep, $kouGuess[$dep]) === (string)$c['index'])>
                                                    {{ $optionText($c) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endfor
                            </div>

                            <div class="pt-1">
                                <button type="submit"
                                        class="inline-flex items-center justify-center px-4 h-10 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                    このマッピングで反映実行
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            @endif

            @if(!empty($years))
                <div class="text-xs text-gray-500">
                    取込済み年度:
                    {{ implode(', ', array_map(static fn ($y) => (string)$y, $years)) }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
