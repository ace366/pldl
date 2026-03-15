<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Child;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceScanController extends Controller
{
    /**
     * スキャン画面
     * GET /admin/attendance/scan
     */
    public function scan(Request $request)
    {
        $this->ensureAdmin($request);

        return view('admin.attendance.scan');
    }

    /**
     * QRスキャン → 出席登録
     * POST /admin/attendance/log   (想定)
     */
    public function log(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'qr' => ['required', 'string', 'max:255'],
        ]);

        $raw = trim((string)$validated['qr']);

        // 受け入れる形式：
        // 1) "CHILD:1234"（おすすめ：安定）
        // 2) "LOGIN:xxxxx"（必要なら）
        // 3) "1234"（child_code 4桁）
        // 4) "123"（旧: children.id）
        $child = $this->resolveChildFromQr($raw);

        if (!$child) {
            return response()->json([
                'ok'      => false,
                'message' => '該当する児童が見つかりません。QRを確認してください。',
            ], 404);
        }

        // 二重登録を軽く防ぐ（同一児童を直近30秒以内に連続登録したら弾く）
        $recentSeconds = 30;

        $already = Attendance::query()
            ->where('child_id', $child->id)
            ->where('attended_at', '>=', now()->subSeconds($recentSeconds))
            ->exists();

        if ($already) {
            return response()->json([
                'ok'       => true,
                'child'    => $this->childPayload($child),
                'message'  => '直近で登録済みです（連続スキャン防止）',
                'tts_text' => null,
            ]);
        }

        DB::transaction(function () use ($child) {
            Attendance::create([
                'child_id'         => $child->id,
                'scanned_by'       => Auth::id(),        // 管理側スキャン者
                'attendance_type'  => 'in',              // 入室（必要なら後でin/out分岐）
                'attended_at'      => now(),
            ]);
        });

        $riddlePayload = $this->buildGreetingPayload($child);

        return response()->json([
            'ok'       => true,
            'child'    => $this->childPayload($child),
            'message'  => '出席を登録しました。',
            'tts_text' => $riddlePayload['question_tts'], // 問題のみ
            'riddle'   => [
                'question'   => $riddlePayload['question'],
                'answer'     => $riddlePayload['answer'],
                'answer_tts' => $riddlePayload['answer_tts'],
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // 内部：QR解析
    // ---------------------------------------------------------------------

    /**
     * QR文字列から児童を特定
     */
    private function resolveChildFromQr(string $raw): ?Child
    {
        // CHILD:1234（4桁コード）
        if (preg_match('/^CHILD:(\d{4})$/u', $raw, $m)) {
            return $this->findByChildCode($m[1]);
        }

        // 4桁だけ（受付で "1234" と読んでもOK）
        if (preg_match('/^\d{4}$/u', $raw)) {
            return $this->findByChildCode($raw);
        }

        // LOGIN:xxxxx（ログインIDや user_code 等を想定）
        if (preg_match('/^LOGIN:(.+)$/u', $raw, $m)) {
            $loginId = trim((string)$m[1]);

            // children.login_id
            if ($this->columnExists('children', 'login_id')) {
                $hit = Child::query()
                    ->with(['school', 'base'])
                    ->where('login_id', $loginId)
                    ->first();
                if ($hit) return $hit;
            }

            // children.user_code
            if ($this->columnExists('children', 'user_code')) {
                $hit = Child::query()
                    ->with(['school', 'base'])
                    ->where('user_code', $loginId)
                    ->first();
                if ($hit) return $hit;
            }

            // 数値なら旧ID扱い
            if (ctype_digit($loginId)) {
                return Child::query()
                    ->with(['school', 'base'])
                    ->find((int)$loginId);
            }

            return null;
        }

        // 数値のみ → 旧: id
        if (ctype_digit($raw)) {
            return Child::query()
                ->with(['school', 'base'])
                ->find((int)$raw);
        }

        return null;
    }

    private function findByChildCode(string $code): ?Child
    {
        // child_codeカラムが無い環境でも落ちないように（導入途中の保険）
        if (!$this->columnExists('children', 'child_code')) {
            return null;
        }

        return Child::query()
            ->with(['school', 'base'])
            ->where('child_code', $code)
            ->first();
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ---------------------------------------------------------------------
    // 内部：返却データ / 読み上げ
    // ---------------------------------------------------------------------

    private function childPayload(Child $child): array
    {
        $name = trim(($child->last_name ?? '') . ' ' . ($child->first_name ?? ''));
        if ($name === '') {
            $name = (string)($child->name ?? '—');
        }

        return [
            'id'         => $child->id,
            'child_code' => $child->child_code ?? null,
            'name'       => $name,
            'school'     => $child->school?->name ?? ($child->school_name ?? '—'),
            'grade'      => isset($child->grade) ? (string)$child->grade : '—',
            'base'       => $child->base?->name ?? '—',
        ];
    }

    /**
     * 読み上げ用ペイロード（問題と答えを分離）
     */
    private function buildGreetingPayload(Child $child): array
    {
        $name  = trim(($child->last_name ?? '') . ' ' . ($child->first_name ?? ''));
        if ($name === '') {
            $name = (string)($child->name ?? '—');
        }

        $grade = (int)($child->grade ?? 1);
        $grade = max(1, min(6, $grade));

        $riddles = $this->gradeRiddles();
        $list = $riddles[$grade] ?? $riddles[1];

        $pick = $list[array_rand($list)];

        return [
            'question'    => (string)$pick['q'],
            'answer'      => (string)$pick['a'],
            'question_tts'=> "{$name}さん、こんにちは。小学{$grade}年生だね。なぞなぞです。{$pick['q']}",
            'answer_tts'  => "こたえは、{$pick['a']}。",
        ];
    }

    /**
     * 学年別なぞなぞ（10個×6学年）
     */
    private function gradeRiddles(): array
    {
        return [
            1 => [
                ['q' => 'あさになると おきて、よるになると ねるもの なーんだ？', 'a' => 'ひと（人）'],
                ['q' => 'ひが でると でて、よるになると きえるもの なーんだ？', 'a' => 'かげ（影）'],
                ['q' => 'ふくと つめたくなるもの なーんだ？', 'a' => 'かぜ（風）'],
                ['q' => 'そらに うかぶ しろい わた なーんだ？', 'a' => 'くも（雲）'],
                ['q' => 'みずの うえを すいすい すすむ もの なーんだ？', 'a' => 'ふね（船）'],
                ['q' => 'まどから はいって、ドアから でないもの なーんだ？', 'a' => 'ひかり（光）'],
                ['q' => 'よるに でる しろい まる なーんだ？', 'a' => 'つき（月）'],
                ['q' => 'くろいのに しろって いわれるもの なーんだ？', 'a' => 'こくばん（黒板）'],
                ['q' => 'たべると おいしいのに たべられないもの なーんだ？', 'a' => 'え（絵）'],
                ['q' => 'おおきいのに みえないもの なーんだ？', 'a' => 'くうき（空気）'],
            ],
            2 => [
                ['q' => 'いつも じぶんの うしろを ついてくるもの なーんだ？', 'a' => 'かげ（影）'],
                ['q' => 'あけると しまって、しめると あくもの なーんだ？', 'a' => 'はさみ'],
                ['q' => 'あしが ないのに あるくもの なーんだ？', 'a' => 'とけい（時計）'],
                ['q' => 'そらを とぶのに はねが ないもの なーんだ？', 'a' => 'ひこうき（飛行機）'],
                ['q' => 'かみは かみでも たべられない かみ なーんだ？', 'a' => 'かみひこうき（紙飛行機）'],
                ['q' => 'はいると ぬれるのに、ぬれないもの なーんだ？', 'a' => 'しゃしん（写真）'],
                ['q' => 'みずを いれたら かるくなるもの なーんだ？', 'a' => 'ふうせん（風船：空気を入れる）'],
                ['q' => 'たべるまえに たたくもの なーんだ？', 'a' => 'たまご（卵）'],
                ['q' => 'よむと ねむくなる もの なーんだ？', 'a' => 'まくら（枕）'],
                ['q' => 'いえの なかで いちばん たかい ところは どこ？', 'a' => 'てんじょう（天井）'],
            ],
            3 => [
                ['q' => 'くちが ないのに しゃべるもの なーんだ？', 'a' => 'ラジオ'],
                ['q' => 'おすと へこむのに、すぐ もどるもの なーんだ？', 'a' => 'スポンジ'],
                ['q' => 'みずが なくても かわくもの なーんだ？', 'a' => 'のど（喉）'],
                ['q' => 'あるくと すすまないのに、のると すすむもの なーんだ？', 'a' => 'エスカレーター'],
                ['q' => 'つかうほど ちいさくなる ぶんぼうぐ なーんだ？', 'a' => 'けしゴム（消しゴム）'],
                ['q' => 'ひとつ ふえると ひとつ へるもの なーんだ？', 'a' => 'かみ（髪）'],
                ['q' => 'よるに でる しろい まる なーんだ？', 'a' => 'つき（月）'],
                ['q' => 'よるになると ひかる みずの たま なーんだ？', 'a' => 'ほし（星）'],
                ['q' => 'いつも まえだけ みている どうぶつ なーんだ？', 'a' => 'うま（馬）'],
                ['q' => 'たまに あるくけど あしがない もの なーんだ？', 'a' => 'ゆび（指）'],
            ],
            4 => [
                ['q' => 'かっても まけても ふえるもの なーんだ？', 'a' => 'けいけん（経験）'],
                ['q' => 'みられるほど はずかしいのに、みられないと こまるもの なーんだ？', 'a' => 'かがみ（鏡）'],
                ['q' => 'でると うれしいのに、でると こまる みず なーんだ？', 'a' => 'あせ（汗）'],
                ['q' => 'くると へるのに、いかないと へらないもの なーんだ？', 'a' => 'しゅくだい（宿題）'],
                ['q' => 'なつに ふるのに つめたい もの なーんだ？', 'a' => 'かきごおり（かき氷）'],
                ['q' => 'よく みると かおに みえる かんじ なーんだ？', 'a' => '田（た）'],
                ['q' => 'うえから よむと さむくて、したから よむと あついもの なーんだ？', 'a' => 'ゆき（雪）となつ（夏）'],
                ['q' => 'あついのに さむいと いうもの なーんだ？', 'a' => 'さむいギャグ'],
                ['q' => 'かくと けせるのに、けすと かけないもの なーんだ？', 'a' => 'えんぴつ（鉛筆の芯）'],
                ['q' => 'いれるほど かるくなる もの なーんだ？', 'a' => 'ふうせん（風船）'],
            ],
            5 => [
                ['q' => 'だれかに あげるほど ふえるもの なーんだ？', 'a' => 'やさしさ（優しさ）'],
                ['q' => 'ふえるほど みえなくなるもの なーんだ？', 'a' => 'きり（霧）'],
                ['q' => 'だれでも もってるのに じぶんでは みえないもの なーんだ？', 'a' => 'せなか（背中）'],
                ['q' => 'うごくほど とまってみえるもの なーんだ？', 'a' => 'こま（独楽）'],
                ['q' => 'なまえを よばれると うれしいのに よばれないと こまるもの なーんだ？', 'a' => 'てんこ（点呼）'],
                ['q' => 'いつも 0なのに ぜったい ひつような すうじ なーんだ？', 'a' => '0（ゼロ）'],
                ['q' => 'みぎに いくほど ちいさくなる かずの ならびは？', 'a' => 'ぶんすう（分数）'],
                ['q' => 'のむほど かわくことがある。なぜ？', 'a' => 'しお（塩分）が多いとき'],
                ['q' => 'つかうと へるのに、へるほど うれしいもの なーんだ？', 'a' => 'しゅくだい（宿題）'],
                ['q' => 'かくほど うすくなる もの なーんだ？', 'a' => 'けしゴム（消しゴム）'],
            ],
            6 => [
                ['q' => 'みぎへ いくほど ちいさくなる かずの ならびは？', 'a' => 'ぶんすう（分数）'],
                ['q' => 'だれでも もってるのに じぶんでは みえないもの なーんだ？', 'a' => 'せなか（背中）'],
                ['q' => 'うごくほど とまってみえるもの なーんだ？', 'a' => 'こま（独楽）'],
                ['q' => 'ふえるほど みえなくなるもの なーんだ？', 'a' => 'きり（霧）'],
                ['q' => 'いつも 0なのに だいじな すうじ なーんだ？', 'a' => '0（ゼロ）'],
                ['q' => 'なまえを よばれると うれしいのに、よばれないと こまるもの なーんだ？', 'a' => 'てんこ（点呼）'],
                ['q' => 'こたえを いうと まちがいになる もんだい なーんだ？', 'a' => 'なぞなぞ'],
                ['q' => 'はやく すすむほど おそくなることがある。なにが？', 'a' => 'じかんの たいかん（時間の体感）'],
                ['q' => 'みずを のむほど のどが かわくことがある。どうして？', 'a' => 'しお（塩分）が多いとき'],
                ['q' => 'もんだいは かんたん。こたえが むずかしい。これ なーんだ？', 'a' => 'じんせい（人生）'],
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // 内部：権限チェック（安全最優先）
    // ---------------------------------------------------------------------
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!RolePermissionService::canUser($user, 'child_qr_scan', 'view')) {
            abort(403);
        }
    }
}
