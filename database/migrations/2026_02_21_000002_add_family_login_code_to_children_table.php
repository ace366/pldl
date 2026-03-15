<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            if (!Schema::hasColumn('children', 'family_login_code')) {
                $table->char('family_login_code', 4)->nullable()->after('child_code');
                $table->index('family_login_code');
            }
        });

        if (Schema::hasColumn('children', 'family_login_code') && Schema::hasColumn('children', 'child_code')) {
            DB::table('children')
                ->whereNull('family_login_code')
                ->whereNotNull('child_code')
                ->update(['family_login_code' => DB::raw('child_code')]);
        }
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            if (Schema::hasColumn('children', 'family_login_code')) {
                $table->dropIndex(['family_login_code']);
                $table->dropColumn('family_login_code');
            }
        });
    }
};
