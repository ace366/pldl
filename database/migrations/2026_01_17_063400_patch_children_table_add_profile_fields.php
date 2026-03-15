<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // 既にある/ない環境差に耐えるように、なければ追加
            if (!Schema::hasColumn('children', 'last_name')) {
                $table->string('last_name', 50)->after('id');
            }
            if (!Schema::hasColumn('children', 'first_name')) {
                $table->string('first_name', 50)->after('last_name');
            }
            if (!Schema::hasColumn('children', 'last_name_kana')) {
                $table->string('last_name_kana', 50)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('children', 'first_name_kana')) {
                $table->string('first_name_kana', 50)->nullable()->after('last_name_kana');
            }

            if (!Schema::hasColumn('children', 'grade')) {
                $table->unsignedTinyInteger('grade')->default(1)->after('first_name_kana');
            }

            if (!Schema::hasColumn('children', 'base_id')) {
                $table->unsignedBigInteger('base_id')->nullable()->after('grade');
                $table->index('base_id');
                // FKは環境差で落ちることがあるので、まずは index のみに（後でFK追加可）
            }

            if (!Schema::hasColumn('children', 'status')) {
                // enum ではなく string にして運用安全性を優先
                $table->string('status', 20)->default('enrolled')->after('base_id');
                $table->index('status');
            }

            if (!Schema::hasColumn('children', 'note')) {
                $table->text('note')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // down は “存在するなら落とす” で安全に
            if (Schema::hasColumn('children', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('children', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('children', 'base_id')) {
                $table->dropIndex(['base_id']);
                $table->dropColumn('base_id');
            }
            if (Schema::hasColumn('children', 'grade')) {
                $table->dropColumn('grade');
            }
            if (Schema::hasColumn('children', 'first_name_kana')) {
                $table->dropColumn('first_name_kana');
            }
            if (Schema::hasColumn('children', 'last_name_kana')) {
                $table->dropColumn('last_name_kana');
            }
            if (Schema::hasColumn('children', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('children', 'last_name')) {
                $table->dropColumn('last_name');
            }
        });
    }
};
