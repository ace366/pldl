<?php

namespace Tests\Unit;

use App\Models\WithholdingTaxTable;
use App\Services\Payroll\WithholdingTaxCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithholdingTaxCalculatorTest extends TestCase
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

    public function test_calc_returns_tax_amount_when_taxable_hits_range(): void
    {
        WithholdingTaxTable::query()->create([
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'kou',
            'dep_count' => 0,
            'min_amount' => 100000,
            'max_amount' => 199999,
            'tax_amount' => 5100,
        ]);

        $calculator = new WithholdingTaxCalculator();
        $tax = $calculator->calc(2026, 180000, 10000, true, 0, 'monthly');

        $this->assertSame(5100, $tax);
    }

    public function test_calc_returns_zero_when_no_range_matches(): void
    {
        WithholdingTaxTable::query()->create([
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'kou',
            'dep_count' => 0,
            'min_amount' => 100000,
            'max_amount' => 199999,
            'tax_amount' => 5100,
        ]);

        $calculator = new WithholdingTaxCalculator();
        $tax = $calculator->calc(2026, 50000, 0, true, 0, 'monthly');

        $this->assertSame(0, $tax);
    }

    public function test_otsu_ignores_dep_count_and_uses_zero(): void
    {
        WithholdingTaxTable::query()->create([
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'otsu',
            'dep_count' => 0,
            'min_amount' => 0,
            'max_amount' => 999999,
            'tax_amount' => 1234,
        ]);
        WithholdingTaxTable::query()->create([
            'year' => 2026,
            'pay_type' => 'monthly',
            'column_type' => 'otsu',
            'dep_count' => 5,
            'min_amount' => 0,
            'max_amount' => 999999,
            'tax_amount' => 9876,
        ]);

        $calculator = new WithholdingTaxCalculator();
        $tax = $calculator->calc(2026, 80000, 0, false, 5, 'monthly');

        $this->assertSame(1234, $tax);
    }
}
