<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_guardian', function (Blueprint $table) {
            if (!Schema::hasColumn('child_guardian', 'relationship')) {
                $table->string('relationship', 30)->nullable()->after('guardian_id');
            }

            // pivot に timestamps を使う設計なら（無い場合のみ追加）
            if (!Schema::hasColumn('child_guardian', 'created_at') && !Schema::hasColumn('child_guardian', 'updated_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('child_guardian', function (Blueprint $table) {
            if (Schema::hasColumn('child_guardian', 'relationship')) {
                $table->dropColumn('relationship');
            }

            // downでtimestampsを落とすのは運用次第（必要なら）
            // $table->dropTimestamps();
        });
    }
};
