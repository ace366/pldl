<?php

namespace App\Services\Payroll;

use RuntimeException;
use ZipArchive;

class WithholdingSpreadsheetConverter
{
    /**
     * 既存互換:
     * ヘッダ行が整っているXLSXを直接CSV化
     */
    public function convertXlsxToCsv(string $xlsxPath, string $csvPath): int
    {
        $sheetRows = $this->extractSheetRows($xlsxPath);
        if ($sheetRows === []) {
            throw new RuntimeException('XLSXの読み取りに失敗しました。');
        }

        $fp = fopen($csvPath, 'wb');
        if ($fp === false) {
            throw new RuntimeException('CSV出力先を作成できませんでした。');
        }

        fputcsv($fp, ['pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount', 'tax_amount']);

        $written = 0;
        foreach ($sheetRows as $rows) {
            $headerIndex = null;
            $map = [];
            foreach ($rows as $i => $row) {
                $mapped = $this->detectHeaderMap($row);
                if ($mapped !== null) {
                    $headerIndex = $i;
                    $map = $mapped;
                    break;
                }
            }
            if ($headerIndex === null) {
                continue;
            }

            $total = count($rows);
            for ($i = $headerIndex + 1; $i < $total; $i++) {
                $row = $rows[$i];
                $payTypeRaw = (string)($row[$map['pay_type']] ?? '');
                $columnTypeRaw = (string)($row[$map['column_type']] ?? '');
                if ($payTypeRaw === '' && $columnTypeRaw === '') {
                    continue;
                }

                $payType = $this->normalizePayType($payTypeRaw);
                $columnType = $this->normalizeColumnType($columnTypeRaw);
                if ($payType === null || $columnType === null) {
                    continue;
                }

                $depCount = $this->toInt($row[$map['dep_count']] ?? '0');
                if ($columnType === 'otsu') {
                    $depCount = 0;
                }

                $minAmount = $this->toInt($row[$map['min_amount']] ?? '0');
                $maxAmount = $this->toInt($row[$map['max_amount']] ?? '0');
                $taxAmount = $this->toInt($row[$map['tax_amount']] ?? '0');

                if ($maxAmount < $minAmount) {
                    continue;
                }

                fputcsv($fp, [$payType, $columnType, $depCount, $minAmount, $maxAmount, $taxAmount]);
                $written++;
            }
        }

        fclose($fp);

        if ($written <= 0) {
            throw new RuntimeException('XLSXから有効な税額表行を抽出できませんでした。');
        }

        return $written;
    }

    /**
     * マッピング画面向け: 列候補の抽出
     *
     * @return array<int,array{
     *  sheet_index:int,
     *  sheet_name:string,
     *  columns:array<int,array{index:int,letter:string,label:string,sample:string}>
     * }>
     */
    public function inspectXlsxColumns(string $xlsxPath): array
    {
        $sheets = $this->extractSheetRows($xlsxPath);
        if ($sheets === []) {
            throw new RuntimeException('XLSXの読み取りに失敗しました。');
        }

        $result = [];
        foreach ($sheets as $sheetIndex => $rows) {
            $maxCol = 0;
            foreach ($rows as $r) {
                if ($r !== []) {
                    $maxCol = max($maxCol, max(array_keys($r)));
                }
            }

            $columns = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $headers = [];
                for ($r = 0; $r < min(14, count($rows)); $r++) {
                    $v = trim((string)($rows[$r][$c] ?? ''));
                    if ($v !== '' && !in_array($v, $headers, true)) {
                        $headers[] = $v;
                    }
                    if (count($headers) >= 4) {
                        break;
                    }
                }

                $sample = '';
                for ($r = 0; $r < min(120, count($rows)); $r++) {
                    $v = trim((string)($rows[$r][$c] ?? ''));
                    if ($v !== '') {
                        $sample = $v;
                        break;
                    }
                }

                $label = $headers !== [] ? implode(' / ', $headers) : '（見出し不明）';
                $columns[] = [
                    'index' => $c,
                    'letter' => $this->columnLetter($c),
                    'label' => $label,
                    'sample' => $sample,
                ];
            }

            $result[] = [
                'sheet_index' => $sheetIndex,
                'sheet_name' => 'Sheet'.($sheetIndex + 1),
                'columns' => $columns,
            ];
        }

        return $result;
    }

    /**
     * NTA横持ち税額表の列マッピングからCSVへ変換
     *
     * @param array{
     *   sheet_index:int,
     *   pay_type:string,
     *   min_col:int,
     *   max_col:int,
     *   otsu_col:int,
     *   kou_columns:array<int,int>
     * } $mapping
     */
    public function convertXlsxByNtaMappingToCsv(string $xlsxPath, string $csvPath, array $mapping): int
    {
        $sheets = $this->extractSheetRows($xlsxPath);
        $sheetIndex = (int)($mapping['sheet_index'] ?? 0);
        if (!isset($sheets[$sheetIndex])) {
            throw new RuntimeException('指定したシートが見つかりません。');
        }
        $rows = $sheets[$sheetIndex];

        $payType = in_array((string)($mapping['pay_type'] ?? ''), ['monthly', 'daily'], true)
            ? (string)$mapping['pay_type']
            : 'monthly';
        $minCol = (int)$mapping['min_col'];
        $maxCol = (int)$mapping['max_col'];
        $otsuCol = (int)$mapping['otsu_col'];
        $kouCols = $mapping['kou_columns'] ?? [];

        if ($kouCols === []) {
            throw new RuntimeException('甲欄の税額列が未指定です。');
        }

        $fp = fopen($csvPath, 'wb');
        if ($fp === false) {
            throw new RuntimeException('CSV出力先を作成できませんでした。');
        }
        fputcsv($fp, ['pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount', 'tax_amount']);

        $written = 0;
        $prevMax = 0;

        foreach ($rows as $row) {
            $minRaw = (string)($row[$minCol] ?? '');
            $maxRaw = (string)($row[$maxCol] ?? '');
            $range = $this->normalizeRange($minRaw, $maxRaw, $prevMax);
            if ($range === null) {
                continue;
            }
            [$minAmount, $maxAmount] = $range;
            $prevMax = $maxAmount;

            $hasTaxCell = false;

            foreach ($kouCols as $depCount => $colIndex) {
                $raw = (string)($row[(int)$colIndex] ?? '');
                if (!$this->hasNumericToken($raw)) {
                    continue;
                }
                $hasTaxCell = true;
                $tax = $this->toInt($raw);
                fputcsv($fp, [$payType, 'kou', (int)$depCount, $minAmount, $maxAmount, $tax]);
                $written++;
            }

            $otsuRaw = (string)($row[$otsuCol] ?? '');
            if ($this->hasNumericToken($otsuRaw)) {
                $hasTaxCell = true;
                $otsuTax = $this->toInt($otsuRaw);
                fputcsv($fp, [$payType, 'otsu', 0, $minAmount, $maxAmount, $otsuTax]);
                $written++;
            }

            if (!$hasTaxCell) {
                // tax列がない行は誤判定の可能性があるため無視
                continue;
            }
        }

        fclose($fp);

        if ($written <= 0) {
            throw new RuntimeException('マッピング条件で有効な税額行を抽出できませんでした。列指定を見直してください。');
        }

        return $written;
    }

    /**
     * @return array<int,array<int,array<int,string>>>
     */
    private function extractSheetRows(string $xlsxPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            return [];
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name) === 1) {
                $sheetFiles[] = $name;
            }
        }
        sort($sheetFiles);

        $sheetRows = [];
        foreach ($sheetFiles as $sheetFile) {
            $xmlRaw = (string)$zip->getFromName($sheetFile);
            if ($xmlRaw === '') {
                continue;
            }
            $xml = @simplexml_load_string($xmlRaw);
            if ($xml === false || !isset($xml->sheetData)) {
                continue;
            }

            $rows = [];
            foreach ($xml->sheetData->row as $rowNode) {
                $row = [];
                foreach ($rowNode->c as $cell) {
                    $ref = (string)($cell['r'] ?? '');
                    $colIndex = $this->columnToIndex($ref);
                    if ($colIndex < 0) {
                        continue;
                    }

                    $value = '';
                    $type = (string)($cell['t'] ?? '');
                    if ($type === 's') {
                        $si = (int)($cell->v ?? -1);
                        $value = (string)($sharedStrings[$si] ?? '');
                    } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                        $value = (string)$cell->is->t;
                    } elseif (isset($cell->v)) {
                        $value = (string)$cell->v;
                    }

                    $row[$colIndex] = trim($value);
                }

                if ($row !== []) {
                    ksort($row);
                    $rows[] = $row;
                }
            }

            if ($rows !== []) {
                $sheetRows[] = $rows;
            }
        }

        $zip->close();
        return $sheetRows;
    }

    /**
     * @return array<int,string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xmlRaw = (string)$zip->getFromName('xl/sharedStrings.xml');
        if ($xmlRaw === '') {
            return [];
        }

        $xml = @simplexml_load_string($xmlRaw);
        if ($xml === false) {
            return [];
        }

        $strings = [];
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string)$si->t;
                continue;
            }

            $chunks = [];
            if (isset($si->r)) {
                foreach ($si->r as $run) {
                    $chunks[] = (string)($run->t ?? '');
                }
            }
            $strings[] = implode('', $chunks);
        }

        return $strings;
    }

    /**
     * @param array<int,string> $row
     * @return array<string,int>|null
     */
    private function detectHeaderMap(array $row): ?array
    {
        $map = [];
        foreach ($row as $index => $label) {
            $key = $this->normalizeHeader($label);
            if ($key !== null) {
                $map[$key] = $index;
            }
        }

        $required = ['pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount', 'tax_amount'];
        foreach ($required as $r) {
            if (!array_key_exists($r, $map)) {
                return null;
            }
        }

        return $map;
    }

    private function normalizeHeader(string $label): ?string
    {
        $v = mb_strtolower(trim($label));
        $v = str_replace([' ', '　', '-', '−', '_'], '', $v);

        $dictionary = [
            'pay_type' => ['paytype', 'pay_type', '支給区分', '支払区分', '月額日額'],
            'column_type' => ['columntype', 'column_type', '甲乙欄', '欄区分'],
            'dep_count' => ['depcount', 'dep_count', '扶養人数', '扶養親族等の数', '扶養親族数'],
            'min_amount' => ['minamount', 'min_amount', '最小金額', '下限金額', '開始金額'],
            'max_amount' => ['maxamount', 'max_amount', '最大金額', '上限金額', '終了金額'],
            'tax_amount' => ['taxamount', 'tax_amount', '税額', '源泉税額', '所得税額'],
        ];

        foreach ($dictionary as $key => $aliases) {
            foreach ($aliases as $alias) {
                $a = str_replace([' ', '　', '-', '−', '_'], '', mb_strtolower($alias));
                if ($v === $a || str_contains($v, $a)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function normalizePayType(string $value): ?string
    {
        $v = mb_strtolower(trim($value));
        if (in_array($v, ['monthly', '月額', '月給', '月'], true)) {
            return 'monthly';
        }
        if (in_array($v, ['daily', '日額', '日給', '日'], true)) {
            return 'daily';
        }
        return null;
    }

    private function normalizeColumnType(string $value): ?string
    {
        $v = mb_strtolower(trim($value));
        if (in_array($v, ['kou', '甲', '甲欄'], true)) {
            return 'kou';
        }
        if (in_array($v, ['otsu', '乙', '乙欄'], true)) {
            return 'otsu';
        }
        return null;
    }

    private function toInt(mixed $value): int
    {
        $s = mb_convert_kana((string)$value, 'n', 'UTF-8');
        $s = preg_replace('/[^\d\-]/', '', $s) ?? '0';
        if ($s === '' || $s === '-') {
            return 0;
        }
        return max(0, (int)$s);
    }

    private function hasNumericToken(string $value): bool
    {
        $s = mb_convert_kana($value, 'n', 'UTF-8');
        return preg_match('/\d+/', $s) === 1;
    }

    /**
     * @return array{0:int,1:int}|null
     */
    private function normalizeRange(string $rawMin, string $rawMax, int $prevMax): ?array
    {
        $hasMinNum = $this->hasNumericToken($rawMin);
        $hasMaxNum = $this->hasNumericToken($rawMax);
        if (!$hasMinNum && !$hasMaxNum) {
            return null;
        }

        $nMin = $this->toInt($rawMin);
        $nMax = $this->toInt($rawMax);

        if ($hasMinNum && $hasMaxNum) {
            $min = $nMin;
            $max = $nMax;
        } elseif ($hasMinNum) {
            // 例: 「105,000円未満」など上限のみ表現
            $min = $prevMax;
            $max = $nMin;
        } else {
            $min = $prevMax;
            $max = $nMax;
        }

        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }
        if ($max <= 0) {
            return null;
        }

        return [$min, $max];
    }

    private function columnToIndex(string $cellRef): int
    {
        if (preg_match('/^([A-Z]+)/', strtoupper($cellRef), $m) !== 1) {
            return -1;
        }

        $letters = $m[1];
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    private function columnLetter(int $index): string
    {
        $n = $index + 1;
        $letters = '';
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $letters = chr(65 + $rem).$letters;
            $n = (int)(($n - 1) / 26);
        }
        return $letters;
    }
}

