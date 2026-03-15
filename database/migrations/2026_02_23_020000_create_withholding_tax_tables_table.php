<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withholding_tax_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('pay_type', 16); // monthly|daily
            $table->string('column_type', 16); // kou|otsu
            $table->unsignedInteger('dep_count')->default(0);
            $table->unsignedInteger('min_amount');
            $table->unsignedInteger('max_amount');
            $table->unsignedInteger('tax_amount');
            $table->timestamps();

            $table->index(
                ['year', 'pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount'],
                'wtt_lookup_idx'
            );
            $table->unique(
                ['year', 'pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount'],
                'wtt_unique_range'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_tables');
    }
};

