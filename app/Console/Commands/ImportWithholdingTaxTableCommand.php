<?php

namespace App\Console\Commands;

use App\Services\Payroll\WithholdingTaxImporter;
use Illuminate\Console\Command;

class ImportWithholdingTaxTableCommand extends Command
{
    protected $signature = 'withholding:import {year : 対象年(YYYY)} {path? : CSVファイルパス}';

    protected $description = '源泉徴収税額表CSVをインポートします（upsert対応）';

    public function handle(WithholdingTaxImporter $importer): int
    {
        $year = (int)$this->argument('year');
        $path = (string)($this->argument('path') ?: storage_path('app/withholding/withholding_tax_'.$year.'.csv'));

        try {
            $count = $importer->import($year, $path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Imported {$count} rows for {$year} from {$path}");

        return self::SUCCESS;
    }
}

