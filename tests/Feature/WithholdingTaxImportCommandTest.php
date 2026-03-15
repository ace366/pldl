<?php

namespace Tests\Feature;

use App\Models\WithholdingTaxTable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithholdingTaxImportCommandTest extends TestCase
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

    public function test_command_imports_and_upserts_tax_table_rows(): void
    {
        $path = $this->makeCsv([
            ['pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount', 'tax_amount'],
            ['monthly', 'kou', '0', '0', '99999', '500'],
        ]);

        $this->artisan('withholding:import', [
            'year' => 2026,
            'path' => $path,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('withholding_tax_tables', [
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'kou',
            'dep_count' => 0,
            'min_amount' => 0,
            'max_amount' => 99999,
            'tax_amount' => 500,
        ]);

        $path2 = $this->makeCsv([
            ['pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount', 'tax_amount'],
            ['monthly', 'kou', '0', '0', '99999', '700'],
        ]);

        $this->artisan('withholding:import', [
            'year' => 2026,
            'path' => $path2,
        ])->assertExitCode(0);

        $this->assertSame(1, WithholdingTaxTable::query()->count());
        $this->assertDatabaseHas('withholding_tax_tables', [
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'kou',
            'dep_count' => 0,
            'min_amount' => 0,
            'max_amount' => 99999,
            'tax_amount' => 700,
        ]);
    }

    /**
     * @param array<int,array<int,string>> $rows
     */
    private function makeCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'withholding_');
        $fp = fopen($path, 'wb');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        return $path;
    }
}
