<?php

namespace Database\Seeders;

use App\Services\Payroll\WithholdingTaxImporter;
use Illuminate\Database\Seeder;

class WithholdingTaxTableSeeder extends Seeder
{
    public function run(): void
    {
        $dir = storage_path('app/withholding');
        if (!is_dir($dir)) {
            $this->command?->warn("withholding directory not found: {$dir}");
            return;
        }

        $files = glob($dir.'/withholding_tax_*.csv') ?: [];
        if ($files === []) {
            $this->command?->warn("CSV not found under {$dir}");
            return;
        }

        $importer = app(WithholdingTaxImporter::class);

        foreach ($files as $path) {
            if (!preg_match('/withholding_tax_(\d{4})\.csv$/', (string)$path, $m)) {
                continue;
            }
            $year = (int)$m[1];
            $count = $importer->import($year, (string)$path);
            $this->command?->info("Imported {$count} rows for {$year} from {$path}");
        }
    }
}

