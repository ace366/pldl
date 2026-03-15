<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_bases', function (Blueprint $table) {
            $table->id();

            // 所属
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('base_id')
                ->constrained('bases')
                ->cascadeOnDelete();

            // 拠点の主所属（将来のUIで便利）
            $table->boolean('is_primary')->default(false);

            // 所属期間（将来の異動・年度切替などに対応）
            $table->date('active_from')->nullable();
            $table->date('active_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 同一ユーザーが同一拠点に重複所属できないように
            $table->unique(['user_id', 'base_id'], 'uq_staff_bases_user_base');

            // 検索最適化
            $table->index(['base_id', 'user_id'], 'idx_staff_bases_base_user');
            $table->index(['user_id', 'is_primary'], 'idx_staff_bases_user_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_bases');
    }
};
