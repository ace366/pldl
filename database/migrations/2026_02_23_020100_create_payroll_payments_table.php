<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('year_month', 7); // YYYY-MM
            $table->unsignedSmallInteger('tax_year');
            $table->string('pay_type', 16)->default('monthly'); // monthly|daily
            $table->string('column_type', 16)->default('kou'); // kou|otsu
            $table->unsignedInteger('dep_count')->default(0);
            $table->unsignedInteger('social_insurance_amount')->default(0);
            $table->unsignedInteger('gross_pay')->default(0);
            $table->unsignedInteger('taxable_amount')->default(0);
            $table->unsignedInteger('withholding_tax')->default(0);
            $table->unsignedInteger('net_pay')->default(0);
            $table->date('closing_date')->nullable(); // 締日（例: 月末）
            $table->date('payment_date')->nullable(); // 支給日（例: 翌月25日）
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'year_month'], 'payroll_payment_user_month_unique');
            $table->index(['year_month', 'user_id'], 'payroll_payment_month_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
    }
};

