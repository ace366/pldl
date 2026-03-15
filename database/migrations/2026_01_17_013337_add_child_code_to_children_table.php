<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // 既存データがあるのでまずnullableで追加（後でNOT NULL化したくなったら別途）
            $table->char('child_code', 4)->nullable()->unique()->after('id');
        });

        // 既存の児童に対して child_code を自動採番して埋める
        $ids = DB::table('children')->whereNull('child_code')->pluck('id');
        foreach ($ids as $id) {
            $code = $this->generateUnique4DigitCode();
            DB::table('children')->where('id', $id)->update(['child_code' => $code]);
        }
    }

    private function generateUnique4DigitCode(): string
    {
        // 0000 は避ける（覚えやすさ＋誤入力対策）
        for ($i = 0; $i < 200; $i++) {
            $n = random_int(1, 9999);
            $code = str_pad((string)$n, 4, '0', STR_PAD_LEFT);

            $exists = DB::table('children')->where('child_code', $code)->exists();
            if (!$exists) return $code;
        }

        throw new RuntimeException('child_code の採番に失敗しました（枯渇 or 競合多発）');
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropUnique(['child_code']);
            $table->dropColumn('child_code');
        });
    }
};
