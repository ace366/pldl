<?php

namespace Tests\Feature;

use App\Services\Payroll\WithholdingSpreadsheetConverter;
use App\Services\Payroll\WithholdingTaxImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class WithholdingSpreadsheetConverterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('withholding_tax_tables')) {
            Schema::drop('withholding_tax_tables');
        }

        Schema::create('withholding_tax_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('pay_type', 16);
            $table->string('column_type', 16);
            $table->unsignedInteger('dep_count')->default(0);
            $table->unsignedInteger('min_amount');
            $table->unsignedInteger('max_amount');
            $table->unsignedInteger('tax_amount');
            $table->timestamps();
            $table->unique(['year', 'pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount']);
        });
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable('withholding_tax_tables')) {
            Schema::drop('withholding_tax_tables');
        }

        parent::tearDown();
    }

    public function test_xlsx_mapping_skips_header_noise_and_builds_expected_first_range(): void
    {
        $converter = new WithholdingSpreadsheetConverter();
        $path = tempnam(sys_get_temp_dir(), 'withholding_xlsx_');

        $count = $converter->convertXlsxByNtaMappingToCsv(
            $this->fixturePath('official_2026_01-07.xlsx'),
            $path,
            [
                'sheet_index' => 0,
                'pay_type' => 'monthly',
                'min_col' => 1,
                'max_col' => 2,
                'otsu_col' => 11,
                'kou_columns' => [0 => 3, 1 => 4, 2 => 5, 3 => 6, 4 => 7, 5 => 8, 6 => 9, 7 => 10],
            ]
        );

        $rows = $this->readCsv($path);
        @unlink($path);

        $this->assertSame(2088, $count);
        $this->assertSame(
            ['monthly', 'kou', '0', '0', '105000', '0'],
            $rows[1]
        );
        $this->assertSame(
            ['monthly', 'otsu', '0', '0', '105000', '3063'],
            $rows[9]
        );
        $this->assertSame(
            ['monthly', 'otsu', '0', '737000', '740000', '257700'],
            $rows[array_key_last($rows)]
        );
    }

    public function test_known_2026_xls_is_converted_via_official_fixture(): void
    {
        $converter = new WithholdingSpreadsheetConverter();
        $path = tempnam(sys_get_temp_dir(), 'withholding_xls_');

        $count = $converter->convertKnownNtaXlsToCsv(
            2026,
            $this->fixturePath('official_2026_01-07.xls'),
            $path
        );

        $this->assertSame(2088, $count);
        $this->assertSame(
            file_get_contents(resource_path('withholding/official_2026_01-07.csv')),
            file_get_contents($path)
        );

        @unlink($path);
    }

    public function test_known_2026_xls_rows_can_be_imported(): void
    {
        $converter = new WithholdingSpreadsheetConverter();
        $importer = new WithholdingTaxImporter();
        $path = tempnam(sys_get_temp_dir(), 'withholding_known_xls_');

        $converter->convertKnownNtaXlsToCsv(
            2026,
            $this->fixturePath('official_2026_01-07.xls'),
            $path
        );
        $count = $importer->import(2026, $path);

        @unlink($path);

        $this->assertSame(2088, $count);
        $this->assertDatabaseHas('withholding_tax_tables', [
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'kou',
            'dep_count' => 0,
            'min_amount' => 0,
            'max_amount' => 105000,
            'tax_amount' => 0,
        ]);
        $this->assertDatabaseHas('withholding_tax_tables', [
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'otsu',
            'dep_count' => 0,
            'min_amount' => 737000,
            'max_amount' => 740000,
            'tax_amount' => 257700,
        ]);
    }

    public function test_importer_rejects_values_that_exceed_unsigned_integer_range(): void
    {
        $importer = new WithholdingTaxImporter();
        $path = tempnam(sys_get_temp_dir(), 'withholding_bad_');
        file_put_contents(
            $path,
            implode("\n", [
                'pay_type,column_type,dep_count,min_amount,max_amount,tax_amount',
                'monthly,kou,0,105000,243311157430122,0',
            ])
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSV内に保存できない金額');

        try {
            $importer->import(2026, $path);
        } finally {
            @unlink($path);
        }
    }

    private function fixturePath(string $name): string
    {
        return base_path('tests/Fixtures/withholding/'.$name);
    }

    /**
     * @return array<int,array<int,string|null>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        foreach ($file as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (count($row) === 1 && ($row[0] === null || trim((string)$row[0]) === '')) {
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
