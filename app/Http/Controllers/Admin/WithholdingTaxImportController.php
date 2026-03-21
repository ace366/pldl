<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithholdingTaxTable;
use App\Services\Payroll\WithholdingSpreadsheetConverter;
use App\Services\Payroll\WithholdingTaxImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WithholdingTaxImportController extends Controller
{
    public function index(Request $request, WithholdingSpreadsheetConverter $converter)
    {
        $year = (int)$request->query('year', (int)Carbon::now()->format('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int)Carbon::now()->format('Y');
        }

        $stats = WithholdingTaxTable::query()
            ->where('year', $year)
            ->selectRaw('pay_type, column_type, COUNT(*) as cnt')
            ->groupBy('pay_type', 'column_type')
            ->orderBy('pay_type')
            ->orderBy('column_type')
            ->get();

        $years = WithholdingTaxTable::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->all();

        $mappingToken = $this->sanitizeToken((string)$request->query('token', ''));
        $mappingSheets = [];
        $mappingSourceName = null;
        if ($mappingToken !== '') {
            $mappingPath = $this->mappingFilePath($mappingToken);
            if (is_file($mappingPath)) {
                try {
                    $mappingSheets = $converter->inspectXlsxColumns($mappingPath);
                    $mappingSourceName = basename($mappingPath);
                } catch (\Throwable $e) {
                    // 無視（画面で通常取込は可能）
                }
            }
        }

        return view('admin.payroll.withholding_import', [
            'year' => $year,
            'stats' => $stats,
            'years' => $years,
            'ntaTopUrl' => 'https://www.nta.go.jp/users/gensen/index.htm',
            'ntaYearUrl' => $this->buildNtaYearUrl($year),
            'defaultCsvPath' => storage_path('app/withholding/withholding_tax_'.$year.'.csv'),
            'mappingToken' => $mappingToken,
            'mappingSheets' => $mappingSheets,
            'mappingSourceName' => $mappingSourceName,
        ]);
    }

    /**
     * 取込準備:
     * - csv/txt はそのまま取込
     * - xlsx はマッピング画面へ遷移
     */
    public function import(
        Request $request,
        WithholdingTaxImporter $importer,
        WithholdingSpreadsheetConverter $converter
    )
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mode' => ['required', 'in:file,url,auto'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'tax_file' => ['nullable', 'file', 'max:51200'],
        ]);

        $year = (int)$data['year'];

        try {
            [$sourcePath, $originalName, $deleteAfter] = $this->resolveSourceFile($request, $year, (string)$data['mode'], (string)($data['source_url'] ?? ''));
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (in_array($ext, ['csv', 'txt'], true)) {
                $targetCsv = storage_path('app/withholding/withholding_tax_'.$year.'.csv');
                $this->ensureWithholdingDir();
                copy($sourcePath, $targetCsv);
                $count = $importer->import($year, $targetCsv);
                if ($deleteAfter && is_file($sourcePath)) {
                    @unlink($sourcePath);
                }

                return redirect()
                    ->route('admin.payroll.withholding.index', ['year' => $year])
                    ->with('success', "{$year}年の税額表を {$count} 行取り込みました。");
            }

            if ($ext === 'xls') {
                $targetCsv = storage_path('app/withholding/withholding_tax_'.$year.'.csv');
                $this->ensureWithholdingDir();
                $count = $converter->convertKnownNtaXlsToCsv($year, $sourcePath, $targetCsv);
                $count = $importer->import($year, $targetCsv);
                if ($deleteAfter && is_file($sourcePath)) {
                    @unlink($sourcePath);
                }

                return redirect()
                    ->route('admin.payroll.withholding.index', ['year' => $year])
                    ->with('success', "{$year}年の税額表を {$count} 行取り込みました。");
            }

            if ($ext === 'xlsx') {
                $token = (string)Str::uuid();
                $this->ensureMappingDir();
                $mappingPath = $this->mappingFilePath($token);
                copy($sourcePath, $mappingPath);
                if ($deleteAfter && is_file($sourcePath)) {
                    @unlink($sourcePath);
                }

                return redirect()
                    ->route('admin.payroll.withholding.index', ['year' => $year, 'token' => $token])
                    ->with('success', 'XLSXを読み込みました。ヘッダをマッピングして「反映実行」してください。');
            }

            throw new \RuntimeException('対応形式は csv / xlsx / xls です。');
        } catch (\Throwable $e) {
            return back()->withErrors(['tax_file' => $e->getMessage()])->withInput();
        }
    }

    /**
     * マッピング確定して反映実行
     */
    public function importMapped(
        Request $request,
        WithholdingTaxImporter $importer,
        WithholdingSpreadsheetConverter $converter
    ) {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'source_token' => ['required', 'string', 'max:64'],
            'sheet_index' => ['required', 'integer', 'min:0'],
            'pay_type' => ['required', 'in:monthly,daily'],
            'min_col' => ['required', 'integer', 'min:0'],
            'max_col' => ['required', 'integer', 'min:0'],
            'otsu_col' => ['required', 'integer', 'min:0'],
            'kou_col_0' => ['nullable', 'integer', 'min:0'],
            'kou_col_1' => ['nullable', 'integer', 'min:0'],
            'kou_col_2' => ['nullable', 'integer', 'min:0'],
            'kou_col_3' => ['nullable', 'integer', 'min:0'],
            'kou_col_4' => ['nullable', 'integer', 'min:0'],
            'kou_col_5' => ['nullable', 'integer', 'min:0'],
            'kou_col_6' => ['nullable', 'integer', 'min:0'],
            'kou_col_7' => ['nullable', 'integer', 'min:0'],
        ]);

        $year = (int)$data['year'];
        $token = $this->sanitizeToken((string)$data['source_token']);
        if ($token === '') {
            return back()->withErrors(['source_token' => 'マッピングトークンが不正です。']);
        }

        $mappingPath = $this->mappingFilePath($token);
        if (!is_file($mappingPath)) {
            return redirect()
                ->route('admin.payroll.withholding.index', ['year' => $year])
                ->withErrors(['source_token' => 'マッピング対象ファイルが見つかりません。再アップロードしてください。']);
        }

        $kouColumns = [];
        for ($dep = 0; $dep <= 7; $dep++) {
            $key = 'kou_col_'.$dep;
            if (isset($data[$key]) && $data[$key] !== null && $data[$key] !== '') {
                $kouColumns[$dep] = (int)$data[$key];
            }
        }
        if ($kouColumns === []) {
            return back()
                ->withErrors(['kou_col_0' => '甲欄（0人以上）の税額列を最低1つ指定してください。'])
                ->withInput();
        }

        try {
            $targetCsv = storage_path('app/withholding/withholding_tax_'.$year.'.csv');
            $converter->convertXlsxByNtaMappingToCsv($mappingPath, $targetCsv, [
                'sheet_index' => (int)$data['sheet_index'],
                'pay_type' => (string)$data['pay_type'],
                'min_col' => (int)$data['min_col'],
                'max_col' => (int)$data['max_col'],
                'otsu_col' => (int)$data['otsu_col'],
                'kou_columns' => $kouColumns,
            ]);

            $count = $importer->import($year, $targetCsv);
            @unlink($mappingPath);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.payroll.withholding.index', ['year' => $year, 'token' => $token])
                ->withErrors(['tax_file' => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.payroll.withholding.index', ['year' => $year])
            ->with('success', "{$year}年の税額表を {$count} 行取り込みました。");
    }

    private function resolveSourceFile(Request $request, int $year, string $mode, string $sourceUrl): array
    {
        if ($mode === 'file') {
            $uploaded = $request->file('tax_file');
            if (!$uploaded) {
                throw new \RuntimeException('ファイルを選択してください。');
            }
            $path = $uploaded->getRealPath();
            if (!$path || !is_file($path)) {
                throw new \RuntimeException('アップロードファイルを読み取れませんでした。');
            }
            return [$path, (string)$uploaded->getClientOriginalName(), false];
        }

        if ($mode === 'auto') {
            $discoveredUrl = $this->discoverLatestExcelUrl($year);
            if ($discoveredUrl === null) {
                throw new \RuntimeException('最新Excelリンクを自動検出できませんでした。URL指定で取り込んでください。');
            }
            $sourceUrl = $discoveredUrl;
        }

        if ($sourceUrl === '') {
            throw new \RuntimeException('URLを入力してください。');
        }

        $response = Http::timeout(30)->get($sourceUrl);
        if (!$response->successful()) {
            throw new \RuntimeException('URLからファイルを取得できませんでした。');
        }
        $content = $response->body();
        if ($content === '') {
            throw new \RuntimeException('取得したファイルが空です。');
        }

        $basename = basename(parse_url($sourceUrl, PHP_URL_PATH) ?: 'withholding_source');
        if ($basename === '' || $basename === '/' || $basename === '.') {
            $basename = 'withholding_source';
        }

        $this->ensureWithholdingDir();
        $tmpPath = storage_path('app/withholding/tmp_'.$year.'_'.time().'_'.$basename);
        file_put_contents($tmpPath, $content);

        return [$tmpPath, $basename, true];
    }

    private function buildNtaYearUrl(int $year): string
    {
        return "https://www.nta.go.jp/publication/pamph/gensen/zeigakuhyo{$year}/01.htm";
    }

    private function discoverLatestExcelUrl(int $year): ?string
    {
        $pageUrl = $this->buildNtaYearUrl($year);
        $res = Http::timeout(30)->get($pageUrl);
        if (!$res->successful()) {
            return null;
        }

        $body = $res->body();
        if ($body === '') {
            return null;
        }

        $utf8 = mb_convert_encoding($body, 'UTF-8', 'UTF-8, SJIS-win, Shift_JIS, EUC-JP');
        preg_match_all('/href=["\']([^"\']+\.(?:xlsx|xls|csv))["\']/i', $utf8, $m);
        $links = $m[1] ?? [];
        if ($links === []) {
            return null;
        }

        $base = rtrim(dirname($pageUrl), '/').'/';
        foreach ($links as $href) {
            if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                return $href;
            }
            if (str_starts_with($href, '/')) {
                return 'https://www.nta.go.jp'.$href;
            }
            return $base.$href;
        }

        return null;
    }

    private function sanitizeToken(string $token): string
    {
        return preg_match('/^[a-f0-9-]{36}$/', $token) === 1 ? $token : '';
    }

    private function mappingFilePath(string $token): string
    {
        return storage_path('app/withholding/mapping/'.$token.'.xlsx');
    }

    private function ensureWithholdingDir(): void
    {
        $dir = storage_path('app/withholding');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function ensureMappingDir(): void
    {
        $dir = storage_path('app/withholding/mapping');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
