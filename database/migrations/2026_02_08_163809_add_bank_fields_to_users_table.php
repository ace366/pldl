<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_code', 4)->nullable()->after('phone');
            $table->string('bank_name', 100)->nullable()->after('bank_code');
            $table->string('bank_branch_code', 3)->nullable()->after('bank_name');
            $table->string('bank_branch_name', 100)->nullable()->after('bank_branch_code');
            $table->string('bank_account_type', 10)->nullable()->after('bank_branch_name');
            $table->string('bank_account_number', 20)->nullable()->after('bank_account_type');
            $table->string('bank_account_holder_kana', 100)->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bank_code',
                'bank_name',
                'bank_branch_code',
                'bank_branch_name',
                'bank_account_type',
                'bank_account_number',
                'bank_account_holder_kana',
            ]);
        });
    }
};
